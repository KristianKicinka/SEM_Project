import requests
import json

API_URL = 'http://localhost:8000/api/create-hash-from-apk'

auth_key = 'j92x4jy6FvaMGusAYUkeh28vB2sSw9'

parameters = {
    'auth_key' : auth_key,
    'hash_types' : json.dumps(['JA3']),
}

files = {
    'apk_file' : open('./data/Netflix_8.97.3.apk', 'rb')
}

response = requests.post(API_URL, files=files, data=parameters)
json_formatted = json.dumps(response.json(), indent=2)

print(json_formatted)