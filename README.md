![webfactory Logo](https://www.webfactory.de/bundles/webfactorytwiglayout/img/logo.png) 
# `secret-spreader` for GitHub Actions

This tool can be used to keep a set of secret values in a Git repository. These secret values can then be distributed as [GitHub Actions Secrets](https://help.github.com/en/actions/automating-your-workflow-with-github-actions/creating-and-using-encrypted-secrets) to a range of GitHub repositories.

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

### DevOps Ninja Extra Credits 🚀

Use GitHub Actions on your "secrets management" repository to roll out the updates whenever changes to the secrets and/or the configuration are committed.

## Usage

_To be written_

## Credits, Copyright and License

This action was written by webfactory GmbH, Bonn, Germany. We're a software development
agency with a focus on PHP (mostly [Symfony](http://github.com/symfony/symfony)). If you're a 
developer looking for new challenges, we'd like to hear from you! 

- <https://www.webfactory.de>
- <https://twitter.com/webfactory>

Copyright 2020 webfactory GmbH, Bonn. Code released under [the MIT license](LICENSE).
