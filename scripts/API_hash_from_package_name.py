import requests
import json

API_URL = 'http://localhost:8000/api/create-hash-from-package-name'

headers = {
    'auth_key' : '8AHniTyqoHztFRZWYwGJ73xdVUCgAV'
}

parameters = {
    'hash_types' : json.dumps(['JA3']),
    'package_name': 'com.facebook.orca'
}

response = requests.post(API_URL, headers=headers, data=parameters)

print(response)

json_formatted = json.dumps(response.json(), indent=2)

print(json_formatted)