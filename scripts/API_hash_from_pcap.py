import requests
import json

API_URL = 'http://localhost:8000/api/create-hash-from-pcap'

headers = {
    'auth_key' : '8AHniTyqoHztFRZWYwGJ73xdVUCgAV'
}

parameters = {
    'hash_types' : json.dumps(['JA3']),
    'app_name' : 'Messenger',
    'package_name': 'com.facebook.orca',
    'app_version': '1.0',
    'is_malware': False
}

files = { 'pcap_file' : open('./data/TikTok.pcap', 'rb')}

response = requests.post(API_URL, headers=headers, files=files, data=parameters)

print(response)

json_formatted = json.dumps(response.json(), indent=2)

print(json_formatted)