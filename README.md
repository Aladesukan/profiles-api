# Profiles API

A RESTful API built with Laravel that creates and manages user profiles by enriching names with demographic data from external APIs.



##Overview

The Profiles API accepts a user's name and automatically generates additional attributes such as:

* Gender (via Genderize API)
* Age (via Agify API)
* Nationality (via Nationalize API)

It also ensures **idempotency**, meaning duplicate names will not create duplicate records.



## Features

* ✅ Create profile with enriched data
* ✅ Prevent duplicate profiles (idempotency)
* ✅ Fetch all profiles
* ✅ Filter profiles by gender
* ✅ Retrieve a single profile
* ✅ Delete a profile
* ✅ External API integration using parallel requests (`Http::pool`)
* ✅ Error handling for failed API responses (returns `502`)
* ✅ Input validation (`400`, `422`, `404`)
* ✅ Automated tests using PHPUnit



Tech Stack

* PHP (Laravel Framework)
* MySQL / SQLite
* Laravel HTTP Client
* PHPUnit for testing



API Endpoints

1. Create Profile

**POST** `/api/profiles`

Request:

```json
{
  "name": "john"
}


Response (201):

```json
{
  "status": "success",
  "data": {
    "id": "uuid",
    "name": "john",
    "gender": "male",
    "age": 30,
    "country_id": "US"
  }
}


 2. Get All Profiles

**GET** `/api/profiles`


 3. Filter by Gender

**GET** `/api/profiles?gender=male`


 4. Get Single Profile

**GET** `/api/profiles/{id}`


 5. Delete Profile

DELETE `/api/profiles/{id}`

Error Handling

| Status Code | Description                     |
| ----------- | ------------------------------- |
| 400         | Missing required field (`name`) |
| 422         | Invalid data type               |
| 404         | Profile not found               |
| 502         | External API failure            |

Example error response:

```json
{
  "status": "error",
  "message": "Failed to fetch data from external APIs"
}

Idempotency

If a profile with the same name already exists:

* The API will **not create a new record**
* It will return the existing profile instead


How It Works

* Uses Laravel’s `Http::pool()` to call:

  * Genderize API
  * Agify API
  * Nationalize API
* All requests run **concurrently** for better performance
* Results are merged and stored in the database



Running Tests
php artisan test


Installation

1. Create repository:

2. Install dependencies:
 OPened laragon app 
clicked on menu and aceesed quickapp which had laravel as part of it's option
clicked it then put in my title


4. Setup environment:
Laragon


5. Configure database in `.env`

6. Run migrations:
php artisan migrate


7. Start server:
php artisan serve



 Notes
External APIs used:
- https://genderize.io
- https://agify.io
- https://nationalize.io
- Ensure internet connection for API calls

Author : Fiyinfoluwa Aladesukan

Aspiring Full-Stack Developer
Passionate about building real-world backend systems

Future Improvements

- Add authentication (JWT / Sanctum)
- Add caching for API responses
- Rate limiting
- Frontend integration


Acknowledgements

This project demonstrates:

1 API design principles
2 Error handling strategies
3 External API integration
4 Clean and testable Laravel architecture

---
