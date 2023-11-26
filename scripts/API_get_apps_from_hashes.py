import requests
import json

API_URL = 'http://localhost:8000/api/get-apps-from-hashes'

hashes = [
    'bead7bc42ae1681f477432be0c0727d0',
    'a0e20bdb405ae40b72f69b8746e07a11',
]

headers = {
    'auth_key' : '8AHniTyqoHztFRZWYwGJ73xdVUCgAV'
}

parameters = {
    'hash_types' : json.dumps(['JA3']),
    'hashes': json.dumps(hashes),
}

response = requests.post(API_URL, headers=headers, data=parameters)

print(response)

json_formatted = json.dumps(response.json(), indent=2)

print(json_formatted)