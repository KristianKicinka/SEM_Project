import requests
import json

API_URL = 'http://localhost:8000/api/get-app-hashes'

apps = [
    {'package_name': 'com.facebook.orca', 'version': '', 'hash_types': ['JA3'] },
    {'package_name': 'com.whatsapp', 'version': '', 'hash_types': ['JA3'] }
]

headers = { 'auth_key' : '8AHniTyqoHztFRZWYwGJ73xdVUCgAV' }

parameters = { 'apps': json.dumps(apps) }

response = requests.post(API_URL, headers=headers, data=parameters)

print(response)

json_formatted = json.dumps(response.json(), indent=2)

print(json_formatted)