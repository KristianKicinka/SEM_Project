import requests
from bs4 import BeautifulSoup
 
if __name__ == '__main__':

    url = "https://d.apkpure.com/b/APK/com.facebook.orca?version=latest"

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

    print(direct_link)
    