import requests
import json

API_URL = 'http://localhost:8000/api/get-apps-from-hashes'

hashes = [
    'bead7bc42ae1681f477432be0c0727d0',
    'a0e20bdb405ae40b72f69b8746e07a11',
]

auth_key = 'wiZ68qvbifI3RHHZbtF6JZAIbzK78K'

parameters = {
    'auth_key' : auth_key,
    'hash_types' : json.dumps(['JA3']),
    'hashes': json.dumps(hashes),
}

response = requests.post(API_URL, data=parameters)

print(response)

json_formatted = json.dumps(response.json(), indent=2)

print(json_formatted)