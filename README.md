# php pipeline with github actions

The idea here is to
- block all direct pushes to master
- build, test and upload coverage on all branches
- merging to master is only done via the github interface and only when it is safe to do so (safe means tests pass).
- testing requires booting a database container
- the schema of the database is external to this repo, because other branches may be ahead of the current one and schema may have already changed

