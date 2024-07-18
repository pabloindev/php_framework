create a password for a user

$password="p";
echo password_hash($password, PASSWORD_DEFAULT);

--------------------------------------------------------------

---------------------------------------------------------------
Assets utilizzati
https://github.com/twbs/icons/releases/tag/v1.11.3

---------------------------------------------------------------
sass --style=compressed --embed-source-map public\assets\scss\main.scss public\assets\css\main.css
sass public\assets\scss\main.scss public\assets\css\main.css