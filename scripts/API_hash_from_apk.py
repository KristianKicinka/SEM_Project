import requests
import json

API_URL = 'http://localhost:8000/api/create-hash-from-apk'

auth_key = 'wiZ68qvbifI3RHHZbtF6JZAIbzK78K'

parameters = {
    'auth_key' : auth_key,
    'hash_types' : json.dumps(['JA3']),
}

files = {
    'apk_file' : open('./data/Messenger_431.1.0.35.116_Apkpure.apk', 'rb')
}

response = requests.post(API_URL, files=files, data=parameters)

print(response)

json_formatted = json.dumps(response.json(), indent=2)

print(json_formatted)