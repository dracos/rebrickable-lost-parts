# Rebrickable Lost Parts bulk entry

Say you’ve been donated a box of Lego, and some manuals, which sort of make up
some sets but probably none complete.

Before donating them onwards, you’d like to sort them out and complete them if
possible, perhaps buying any missing parts.

To do this on <a href="https://rebrickable.com/">Rebrickable</a>, it appears
you have to add the set to your collection, then go through and mark every
missing part one by one, taking a good number of clicks for each part (click on
part, click on Lost Parts, enter number, click save, close part, repeat).

So I made this, which will add multiple things to your Lost Parts at once,
remember where you’ve got to, and make the process a lot smoother.

There is an online version at https://dracos.co.uk/made/rebrickable-lost-parts/
but if you do not want to provie your Rebrickable username/password, you can
install this code yourself.

## Installation

1. Clone the repo.
1. In Rebrickable, go to Account, Settings, API, and generate an API key.
1. Go to https://rebrickable.com/api/v3/docs/ , enter your API key in the box just under Swagger Documentation, scroll down to `POST /api/v3/users/_token/`, expand it, enter your username and password, click Try it out! and note the user token you are given.
1. Copy config.ini-example to config.ini and put your API key and user token inside the new file.
1. Run `php -S localhost:8000` in the repo.
1. Visit http://localhost:8000/ in your browser.

![Screenshot in use](https://github.com/user-attachments/assets/726bcb61-08e2-4ce3-95a5-3b66ac8b3834)
