![webfactory Logo](https://www.webfactory.de/bundles/webfactorytwiglayout/img/logo.png) 
# `secret-spreader` for GitHub Actions

With this tool, you can keep a list of secret values in a Git repository. These secret values can then be distributed
as [GitHub Actions Secrets](https://help.github.com/en/actions/automating-your-workflow-with-github-actions/creating-and-using-encrypted-secrets)
to a list of GitHub repositories, maintained in a config file.

**Attention** *This tool is in a very early development phase. Significant changes to usage, applicability, configuration syntax etc. may be possible and can happen on short or without prior notice.*

**Update 2020-05-14** GitHub [added secrets at the organization level](https://github.blog/changelog/2020-05-14-organization-secrets/) as a native feature for Action. You might be better off working with that officially supported feature instead of using this tool. 

## Why?

When using GitHub Actions to build, test or deploy your software, you will probably need to have access tokens, SSH deployment keys or other secret data available. Chances are that some of these secrets will be used on more than just one or a few repositories. 

For example, think of an SSH key needed to deploy your code to a staging environment. If you want to run these deployments for many different project, you have two choices:

- Have a different SSH key for every repository
- Use a common (shared) SSH key for all repositories

The first approach limits the exposure in case the secret leaks. Managing and keeping track of a large set of keys, however, can be challenging as well. Key/secret rotation has to happen for each key individually.

The second approach makes administration much easier because there is just one key to deal with. But of course, if that key leaks... well.

The `secret-spreader` tool from this repository can help you in _both_ cases.

## How?

With `secret-spreader`, you can commit all your GitHub Actions secrets into a centralized "secrets management" repository. Don't worry, the secrets will be kept encrypted, so that's probably a safe thing to do 🤞🏻.

Along with these secrets goes a configuration file that describes which secret needs to go to which repository or even repositories. That configuration also contains the secret name to be used, which can be different for every target repository.

`secret-spreader` will then use the [GitHub API (beta)](https://developer.github.com/v3/actions/) to make sure all target repositories are configured accordingly.

This approach follows the "Infrastructure as Code" philosophy: The configuration file gives you an up-to-date list of who is using which secret. Having it under version control means you can track who changed what, and you can also revert changes in case something breaks.

### DevOps ninja extra credits 🚀

Use GitHub Actions on your "secrets management" repository to roll out the updates whenever changes to the secrets and/or the configuration are committed.

## Usage

### Have PHP ^7.2.5 installed

_For now, we'll just assume this is the case._ (TODO) 

### Create and checkout your own config repository

Create a Git repository that will contain your own configuration and secrets. To make this easier, we provide a template
repository that [can easily be forked](https://github.com/webfactory/secret-spreader-config-template/generate). Make your fork a private repository.

Then, clone this config repository to your local machine using `git clone <URL-of-your-fork>` and `cd` into it. 

### Initialize `secret-spreader` as a `git submodule`

To make `secret-spreader` easily available, run this in your config repository working area:

```shell script
git submodule add https://github.com/webfactory/github-secret-spreader.git bin
cd bin
git checkout 0.1.1
cd ..
git commit -m "Setup secret-spreader submodule" bin
```

### Initialize your own keypair for encrypting secrets

Assuming you're still in the config repository working area, run `bin/setup`. This command does two things, so let's deal with both in order:

First, it prints a _private_ key 🔐. This key is needed to actually read all the secrets that you will be keeping in this repository. This is the
only time this key will be printed or available. *If you lose this key, you lose access to all the secrets managed in your config repository.*

Grab the key, which will look like `c+5wLYqwzEYJ5H3aJocXd3OY8LtTY1U16CQWi0G74xo=`. Go to the Secrets section of your repository at 
`https://github.com/<your-name>/<your-fork>/settings/secrets` and add a new secret there. Name the secret `SPREAD_PRIVATE_KEY` and use the private key
value that was just printed as the secret value.

Second, back in your local working area, a `spreaded-secrets.yml` config file has been initialized. Keep it using `git add spreaded-secrets.yml` and
`git commit -m 'Add config file'`. (Don't `git push`, yet.)

### Provide a suitably scoped access token to use the API

`secret-spreader` will need a "Personal Access Token" with sufficient permissions to update all the repositories you'd like to manage through the API.
Go create that token now and add it as a second secret named `SPREAD_GITHUB_TOKEN` on your config repository fork.

Double-check: There should be two secrets set up now ✅.

### Encrypt a secret for usage with `secret-spreader`

To see everything is working correctly, let's use `secret-spreader` to add a `DEMO` secret to the config repository itself.

Still in your local working area, run this command:

```shell
bin/encrypt > demo-secret <<EOF
> dont tell mum
> EOF  
```

This will pass the string `dont tell mum` (plus a trailing newline, FWIW) through `bin/encrypt`. That script will _encrypt_ the secret value
and write the resulting (encrypted) value to standard output, which in turn is redirected into the file `demo-secret`. 

Now, edit `spreaded-secrets.yml`:

```diff
 public-key: RKsGpaI3ha+ZGHNZhlhXZVZzceZ5DVT3hpo/OdGfN0E=
 repositories:
-    octocat/example-repo:
-        FIRST_SECRET: secret-file
-        ANOTHER_SECRET: another-file
+    my-name/my-config-repo-fork:
+        DEMO: demo-secret
```  

This will tell the `secret-spreader` to (also) manage the `my-name/my-config-repo-fork` repository at GitHub. This repository should have a secret
named `DEMO` whose value should be what you put into the `demo-secret` file.

Add and commit all those changes: `git add demo-secret spreaded-secrety.yml`, `git commit -m 'Try a DEMO'`.

### Push to deploy 🚨

Now, `git push` your changes.

If all goes well, the `.github/workflows/spread-secrets.yml` should start and configure the `DEMO` secret in your repository.

### Removing a secret
  
If you want to remove a particular secret from a repository, use `~` as the filename for it, like so:

```diff
 public-key: RKsGpaI3ha+ZGHNZhlhXZVZzceZ5DVT3hpo/OdGfN0E=
 repositories:
     mpdude/secret-spreader-walkthrough:
-        DEMO: demo-secret
+        DEMO: ~
```

Try this and remove the `DEMO` secret from your config repository.

### Write a README ⭐️

If you want to do your future self and/or your colleagues a favor, update the `README` file in your config repository fork.

Leave a short notice on how to use the tool, what policies and processes are in place when secrets need to be changed and
all the nitty-gritty details necessary to make things work for you 🦖. 

## Credits, Copyright and License

This tool was written by webfactory GmbH, Bonn, Germany. We're a software development
agency with a focus on PHP (mostly [Symfony](http://github.com/symfony/symfony)). If you're a 
developer looking for new challenges, we'd like to hear from you! 

- <https://www.webfactory.de>
- <https://twitter.com/webfactory>

Copyright 2020 – 2022 webfactory GmbH, Bonn. Code released under [the MIT license](LICENSE).
