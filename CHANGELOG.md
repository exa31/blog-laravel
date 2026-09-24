## [1.8.2](https://github.com/exa31/blog-laravel/compare/v1.8.1...v1.8.2) (2026-09-24)


### Bug Fixes

* **server:** remove invalid caddyfile override to let octane use native frankenphp worker ([050ceaa](https://github.com/exa31/blog-laravel/commit/050ceaa2789ea912cd4f008564fb9ee7a6030f4c))

## [1.8.1](https://github.com/exa31/blog-laravel/compare/v1.8.0...v1.8.1) (2026-09-24)


### Bug Fixes

* **logging:** enable stdout stream and caddy access logging in frankenphp ([61ed8bb](https://github.com/exa31/blog-laravel/commit/61ed8bbf26109ef3289cc64f03442e7690ce2fc3))

# [1.8.0](https://github.com/exa31/blog-laravel/compare/v1.7.0...v1.8.0) (2026-09-24)


### Features

* **ui:** add custom animated confirmation modal and replace browser confirm ([b21a743](https://github.com/exa31/blog-laravel/commit/b21a743c486de1640ffdf3c80e45f17d063562ba))

# [1.7.0](https://github.com/exa31/blog-laravel/compare/v1.6.0...v1.7.0) (2026-09-24)


### Features

* **profile:** add display name update feature in account settings modal ([f28732a](https://github.com/exa31/blog-laravel/commit/f28732a9f802ca6daeaa6be6aff4369465f5f57b))

# [1.6.0](https://github.com/exa31/blog-laravel/compare/v1.5.0...v1.6.0) (2026-09-24)


### Features

* **logging:** implement clean structured full JSON logging for requests and application ([5fb2285](https://github.com/exa31/blog-laravel/commit/5fb2285a2a6aa1e3843bdb922ba2c1015bb43c5b))

# [1.5.0](https://github.com/exa31/blog-laravel/compare/v1.4.0...v1.5.0) (2026-09-24)


### Features

* **auth:** add email OTP verification flow and flexible apple unbind rules ([378e7c8](https://github.com/exa31/blog-laravel/commit/378e7c80db28add33052d50b2dc4dab29283ee73))

# [1.4.0](https://github.com/exa31/blog-laravel/compare/v1.3.0...v1.4.0) (2026-09-24)


### Features

* **auth:** make apple p8 private key optional with public key jwks validation ([bb9fcd6](https://github.com/exa31/blog-laravel/commit/bb9fcd6b42194312258af7e1f51cb9345a9b1bca))

# [1.3.0](https://github.com/exa31/blog-laravel/compare/v1.2.0...v1.3.0) (2026-09-24)


### Features

* **auth:** add set-password step for Google and Apple sign in registrations ([6af9903](https://github.com/exa31/blog-laravel/commit/6af9903adef272259cee11f8eb47fd81c3e7823f))

# [1.2.0](https://github.com/exa31/blog-laravel/compare/v1.1.1...v1.2.0) (2026-09-24)


### Features

* **auth:** add apple sign in, revocation webhook, resend forgot password, and identity binding rules ([ac823c5](https://github.com/exa31/blog-laravel/commit/ac823c5567c90415b560d23a15317ac980048e77))

## [1.1.1](https://github.com/exa31/blog-laravel/compare/v1.1.0...v1.1.1) (2026-09-24)


### Bug Fixes

* **docker:** install composer dependencies with full app context and cleanup composer cache ([2a4a006](https://github.com/exa31/blog-laravel/commit/2a4a006a9c0e36f9b9fdbc87818d996465a3eb99))

# [1.1.0](https://github.com/exa31/blog-laravel/compare/v1.0.1...v1.1.0) (2026-09-24)


### Features

* **deps:** move fakerphp/faker to require for production simulation jobs ([4dbd514](https://github.com/exa31/blog-laravel/commit/4dbd514a7da228f5aca0958ca39fc3011e00920f))

## [1.0.1](https://github.com/exa31/blog-laravel/compare/v1.0.0...v1.0.1) (2026-09-24)


### Performance Improvements

* **docker:** optimize image size with multi-stage alpine build and clean dockerignore ([630d0cc](https://github.com/exa31/blog-laravel/commit/630d0ccaf2767b2513e7ae961a2154afaf4d7477))

# 1.0.0 (2026-09-24)


### Bug Fixes

* bug on add to save post ([ef5fa3a](https://github.com/exa31/blog-laravel/commit/ef5fa3a8628a3c2ad80c1838329f823fa03e3258))
* logout ([ca51184](https://github.com/exa31/blog-laravel/commit/ca511848fb6479bc5cee4e6da320f6e29bbc25e1))
* on home page save post ([9b7dfc5](https://github.com/exa31/blog-laravel/commit/9b7dfc574f5fa1e8aeed110815d4f4f2b825d5c0))
* user access filament ([57b76fd](https://github.com/exa31/blog-laravel/commit/57b76fd911fcf1e362163a2cee8b789d5f8112f5))


### Features

* add middleware trust proxies ([b7c6017](https://github.com/exa31/blog-laravel/commit/b7c60174cd30f727fa21cfc31c930e48f8ec483c))
* add migration table session ([916399d](https://github.com/exa31/blog-laravel/commit/916399dc27a7ed58acb7ac178c0a6244da86e8bf))
* change runtime to franken php use octane - add cicd ([f03d335](https://github.com/exa31/blog-laravel/commit/f03d335e1d705aec4681f5494078185691dce0b9))
* **docker:** auto run database migrations on container startup ([73154af](https://github.com/exa31/blog-laravel/commit/73154af8f095a685efc6c63ee7a510e58218435c))
* **docker:** run database migration asynchronously in background on startup ([2bc4d2e](https://github.com/exa31/blog-laravel/commit/2bc4d2e69ba94856276faf5e73a7e70c6516903e))
* froce scheme to https ([6d6da23](https://github.com/exa31/blog-laravel/commit/6d6da235a5428d3c5fda1ae518c2169957d41ca2))
* **news:** integrate real news api and author attribution, untrack env ([c0199b0](https://github.com/exa31/blog-laravel/commit/c0199b040f795d8402d68113f5ff81083a9ecb2e))
* revised cicd ([b95f14b](https://github.com/exa31/blog-laravel/commit/b95f14b25912e07b4ba127e0479fed13600c7582))
* revised cicd ([75d6947](https://github.com/exa31/blog-laravel/commit/75d6947d34fd799fcab4010474d9bfae0ae9cdaf))
* revised cicd - revised Dockerfile - revised start.sh ([a067030](https://github.com/exa31/blog-laravel/commit/a067030cdbc7ef1ba8080ae10e91d74481b06be5))
* revised docker file and start.sh ([d9eb133](https://github.com/exa31/blog-laravel/commit/d9eb1330dff5c79de1d04057b8cd69e510cc7d87))
* update composer.json ([272c6aa](https://github.com/exa31/blog-laravel/commit/272c6aa143cb2ff86898b163c44c9ca9e935b906))
* update delete save post where post status is draft ([7cbf5b8](https://github.com/exa31/blog-laravel/commit/7cbf5b89983e9e81ab95fe7b7b40b6fc1b14cce2))
