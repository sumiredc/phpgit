# phpgit

```sh
php ./src/app.php git:{command-name}

# use docker container
docker compose run --rm php git:{command-name}
```

## features

- `git:init`
- `git:cat-file [-t | -s | -e | -p] <file>`
- `git:hash-object <file>`
- `git:ls-files [--stage | --debug | -t | -z]`
- `git:update-index [--add <file> | --remove <file> | --force-remove <file> | --cacheinfo <mode> <object> <file>]`


