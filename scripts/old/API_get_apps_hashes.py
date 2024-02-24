import requests
import json

API_URL = 'http://localhost:8000/api/get-app-hashes'

apps = [
    {'package_name': 'com.facebook.orca', 'version': '', 'hash_types': ['JA3'] },
    {'package_name': 'com.spotify.music', 'version': '', 'hash_types': ['JA3'] },
    {'package_name': 'com.netflix.mediaclient', 'version': '', 'hash_types': ['JA3'] }
]

auth_key = 'j92x4jy6FvaMGusAYUkeh28vB2sSw9'

parameters = { 'apps': json.dumps(apps), 'auth_key' : auth_key }

response = requests.post(API_URL, data=parameters)

print(response)

json_formatted = json.dumps(response.json(), indent=2)

print(json_formatted)