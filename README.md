# Soccer Teams

An application to manage soccer teams and its players.

## Requirements
- PHP v8.1
- MySQL (5.7 or 8)/Postgres (11 or Higher)/Sqlite

## Installation and Setup

- Clone this repository to your local.
- Run `composer install` on the root directory to install the composer packages.
- Copy and paste the **.env.example** file into **.env** in the root directory.
- Run `php artisan key:generate` to set the application key in the **.env** file.
- Set all the necessary environment variables in the **.env** file.
- Run `php artisan migrate` to create database (if required) and to execute the migration files.
- To create admin user, either run `php artisan db:seed` or `php artisan user:add`.
- For `php artisan db:seed`, open `database/seeders/DatabaseSeeder.php` and uncomment the user creation script then run `php artisan db:seed`.
- On running `php artisan user:add`, you can set the user as admin by confirming on the prompt asked on executing the command.
- Run `php artisan route:list` to see all the available routes. Else refer to [API Route List](#available-route-list) in the **README.md** file.
- Run `php artisan serve` to start the server.
- Now visit `http://127.0.0.1:8000` in the browser and see {"message":"success"} json to confirm server started properly.
- For postman collection please use the following link to import. 
```
https://api.postman.com/collections/13323251-cfb6f90e-20a8-4ed1-a776-c136934c3c10?access_key=PMAT-01GZVA67KPQPDZS67A631NAK2J
```
- Use the Create Team, Create Player routes to add new teams and players.
- To update use Update Team and Update Player routes.
- To delete use Delete Team and Delete Player routes.
- Once added, to list the teams and players visit Get Teams and Get Players API respectively.

## Testing

- Once cloned and setup is done.
- Open terminal on the project root directory in your local and run `php artisan test` command.
- Once executed it will list all the test class name along with the executed test case and status in detail.

## Available API Route list

#### Login

POST `api/v1/login`

#### Logout

DELETE `api/v1/logout`

#### Get all Teams

GET|HEAD `api/v1/teams`

#### Create a new Team

**Header:** `Authorization: Bearer {token}`

**URI:** POST `api/v1/teams`

#### Get specified Team

**URI:** GET|HEAD `api/v1/teams/{team}`

#### Update specified Team

**Header:** `Authorization: Bearer {token}`

**URI:** PUT|PATCH `api/v1/teams/{team}`

#### Delete specified Team

**Header:** `Authorization: Bearer {token}`

**URI:** DELETE `api/v1/teams/{team}`

#### Get all Players on the specified Team

**URI:** GET|HEAD `api/v1/teams/{team}/players`

#### Create a new Player on the specified Team

**Header:** `Authorization: Bearer {token}`

**URI:** POST `api/v1/teams/{team}/players`

#### Get specified Player on the specified Team

**URI:** GET|HEAD `api/v1/teams/{team}/players/{player}`

#### Update specified Player on the specified Team

**Header:** `Authorization: Bearer {token}`

**URI:** PUT|PATCH `api/v1/teams/{team}/players/{player}`

#### Delete specified Player on the specified Team

**Header:** `Authorization: Bearer {token}`

**URI:** DELETE `api/v1/teams/{team}/players/{player}`
