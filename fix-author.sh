git filter-branch --env-filter '
if [ "$GIT_AUTHOR_EMAIL" = "acbonilla1226@gmail.com" ]
then
    export GIT_AUTHOR_NAME="Camilo"
    export GIT_AUTHOR_EMAIL="camilobonillab26@gmail.com"
fi
if [ "$GIT_COMMITTER_EMAIL" = "acbonilla1226@gmail.com" ]
then
    export GIT_COMMITTER_NAME="Camilo"
    export GIT_COMMITTER_EMAIL="camilobonillab26@gmail.com"
fi
' --tag-name-filter cat -- --branches --tags