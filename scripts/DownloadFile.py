import requests
import sys
from bs4 import BeautifulSoup
import re

directory = "../storage/app/public/uploads/apk_downloaded/"
 
if __name__ == '__main__':

    url = sys.argv[1]   ## First program argument (url)
    file_prefix = sys.argv[2]   ## File Prefix

    response = requests.get(
        url = 'https://proxy.scrapeops.io/v1/',
        params = {
            'api_key': 'e671cd20-97ae-4345-91f7-aa2ce10dcfab',
            'url': url, ## Cloudflare protected website 
            'bypass': 'cloudflare',
            'follow_redirects':'false',
        },
    )

    soup = BeautifulSoup(response.content,"html.parser")

    direct_link = soup.find("a").get("href")

    file = requests.get(direct_link)

    # https://stackoverflow.com/questions/31804799/how-to-get-pdf-filename-with-python-requests
    file_name = re.findall("filename=(.+)", file.headers['content-disposition'])[0][1:-1]
    file_name = file_name.replace(" ", "_")

    file_path = directory+file_prefix+"_"+file_name

    open(file_path, "wb").write(file.content)

    print(file_prefix+"_"+file_name)
    