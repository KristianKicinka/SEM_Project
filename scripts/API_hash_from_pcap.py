import requests
import json

API_URL = 'http://localhost:8000/api/create-hash-from-pcap'

new_auth_key = '8RvicTuKLIHgGQ8mEBbiqwR0hpt7Q3'
old_auth_key = '8AHniTyqoHztFRZWYwGJ73xdVUCgAV'

headers = {
    'auth_key' : new_auth_key
}

parameters = {
    'auth_key' : old_auth_key,
    'hash_types' : json.dumps(['JA3']),
    'app_name' : 'TikTok',
    'package_name': 'com.tiktok',
    'app_version': '1.0',
    'is_malware': False
}

files = { 'pcap_file' : open('./data/TikTok.pcap', 'rb')}

response = requests.post(API_URL, headers=headers, files=files, data=parameters)

print(response)

print(response.request.headers)
print(response.request.body)

json_formatted = json.dumps(response.json(), indent=2)

print(json_formatted)