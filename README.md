# oauth_esgi

This is a project for the course `Intégration SDK`. The goal is to create an OAuth SDK with multiple services like Facebook, Google, Twitter... and to use our own OAuth server to perform OAuth.

## OAuth services used

- Google (https://developers.google.com/identity/sign-in/web/sign-in)
- Facebook (https://developers.facebook.com/docs/facebook-login/web/)
- Twitch (https://dev.twitch.tv/docs/authentication)
- Our own SDK server

## Run the project

### Clone the project 

Use `git clone https://github.com/MisterGoodDeal/oauth_esgi.git`


### Run the project with Docker

***Make sure the ports `80` (for HTTP), `443` (for HTTPS) and `8081` (for our own OAuth server) are free otherwise the docker won't start***

Run the command `docker-compose up` in the root directory

## Accessing the project

Open your favorite web browser and open `https://localhost`
