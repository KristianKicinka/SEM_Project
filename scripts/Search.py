import sys
import requests
from bs4 import BeautifulSoup

def find_package_name(AppName):
    AppName.strip().lower()
    url = "https://play.google.com/store/search?q="+AppName+"&c=apps"
    response = requests.post(url)

    soup = BeautifulSoup(response.content, 'html.parser')
    my_element = soup.find("a", {"class": "Qfxief"})['href']

    return my_element.split("id=", 1)[1]

if __name__ == '__main__':
    app_name = sys.argv[1]
    package_name = find_package_name(app_name)
    print(package_name)