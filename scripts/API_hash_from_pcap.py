import requests
import json

API_URL = 'http://localhost:8000/api/create-hash-from-pcap'

auth_key = 'wiZ68qvbifI3RHHZbtF6JZAIbzK78K'

parameters = {
    'auth_key' : auth_key,
    'hash_types' : json.dumps(['JA3']),
    'app_name' : 'TikTok',
    'package_name': 'com.tiktok',
    'app_version': '1.0',
    'is_malware': False
}

files = { 'pcap_file' : open('./data/TikTok.pcap', 'rb')}

response = requests.post(API_URL, files=files, data=parameters)

print(response)

json_formatted = json.dumps(response.json(), indent=2)

print(json_formatted)