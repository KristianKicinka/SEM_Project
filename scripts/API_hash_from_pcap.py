import requests
import json

API_URL = 'http://localhost:8000/api/create-hash-from-pcap'

auth_key = 'j92x4jy6FvaMGusAYUkeh28vB2sSw9'

parameters = {
    'auth_key' : auth_key,
    'hash_types' : json.dumps(['JA3']),
    'app_name' : 'Messenger',
    'package_name': 'com.facebook.orca',
    'app_version': 'custom',
    'is_malware': False
}

files = { 'pcap_file' : open('./data/JA3_messenger.pcap', 'rb')}

response = requests.post(API_URL, files=files, data=parameters)
json_formatted = json.dumps(response.json(), indent=2)

print(json_formatted)