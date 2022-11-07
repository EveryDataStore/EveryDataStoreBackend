# EveryDataStoreBackend
## Overview

These are list of steps which need to be follow to commit the code on the specific branch for different project release / task. After proper finalize the task need to be merge into master main branch.

## Table of contents

* [Clone the master branch repository]
* [Create a new branch for development]
* [Switch to specific branch]
* [Perform the tasks and commit the changes on the Development branch]
* [After do regression testing on the development branch, release / merge the changes on the master branch]
* [Git log - To check the history of the commits]
* [Managing releases in a repository]

##### Clone the master branch repository
```sh
# Clone the master branch repository
$ git clone https://github.com/EveryDataStore/EveryDataStoreFrontend.git
```

##### Create a new branch for development
```sh
# Creating a local branch
$ git checkout -b development
```

##### Switch to specific branch
```sh
# lists all branches
$ git branch

# switches to your branch
$ git checkout development
```

##### Perform the tasks and commit the changes on the Development branch
```sh
# Check the list of modified files related to performed tasks
$ git status

# Adding a file
$ git add filename

# Adding all files
$ git add -A

# Adding all files changes in a directory
$ git add .

# After adding a file, the next step is to commit staged file(s)
$ git commit -m 'commit message'

# Pushes to your current branch
$ git push origin development 
```
##### After do regression testing on the development branch, release / merge the changes on the master branch
```sh
# First checkout trunk/master
$ git checkout master

# Now merge branch to trunk/master
$ git merge development
```

#### Git log

```sh
# Show a list of all commits in a repository. This command shows everything about a commit, such as commit ID, author, date and commit message.
$ git log

# List of commits showing commit messages and changes
$ git log -p

# List of commits with the particular expression you are looking for
$ git log -S 'something'

# List of commits by author
$ git log --author 'Author Name'

# Show a list of commits in a repository in a more summarised way. This shows a shorter version of the commit ID and the commit message.
$ git log --oneline

# Show a list of commits in a repository since yesterday
$ git log --since=yesterday

# Shows log by author and searching for specific term inside the commit message
$ git log --grep "term" --author "name"
```

### Managing releases in a repository
https://docs.github.com/en/repositories/releasing-projects-on-github/managing-releases-in-a-repository
