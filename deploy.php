<?php

/*
 * Deployer recipe (PLAN M22, A12). GitHub Actions runs `dep deploy production` (or staging) from
 * `main`; see .github/workflows/deploy.yml and docs/ops/server.md. Hosts come from the environment,
 * so no server address or user lives in the repo.
 *
 *   DEPLOY_HOST, DEPLOY_USER (default "deploy"), DEPLOY_PATH (default /var/www/work-atlas)
 */

namespace Deployer;

require 'recipe/laravel.php';

set('application', 'work-atlas');
set('repository', 'https://github.com/JayedHasan232/work-atlas.git');
set('keep_releases', 5);
set('composer_options', '--no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader');

foreach (['production', 'staging'] as $stage) {
    host($stage)
        ->setHostname(getenv('DEPLOY_HOST') ?: 'DEPLOY_HOST-is-not-set')
        ->setRemoteUser(getenv('DEPLOY_USER') ?: 'deploy')
        ->setDeployPath(getenv('DEPLOY_PATH') ?: '/var/www/work-atlas')
        ->setLabels(['stage' => $stage]);
}

desc('Builds the front end and the SSR bundle on the server');
task('build:assets', function (): void {
    cd('{{release_path}}');
    run('npm ci --no-audit --no-fund');
    run('npm run build:ssr');
    run('rm -rf node_modules');
});

desc('Reloads PHP-FPM so OPcache serves the new release (sudo rule from ops/provision.sh)');
task('reload:php-fpm', function (): void {
    run('sudo /usr/bin/systemctl reload php8.4-fpm');
});

desc('Restarts Horizon and the SSR server so they load the new release');
task('restart:workers', function (): void {
    cd('{{current_path}}');
    run('{{bin/php}} artisan horizon:terminate');
    run('{{bin/php}} artisan inertia:stop-ssr || true');
});

desc('Checks the new release answers on /up');
task('smoke:health', function (): void {
    // Nginx serves the app on 127.0.0.1:8080 for this check (ops/nginx/work-atlas.conf).
    run('curl --fail --silent --show-error --max-time 10 http://127.0.0.1:8080/up > /dev/null');
});

after('deploy:vendors', 'build:assets');
after('deploy:symlink', 'reload:php-fpm');
after('artisan:reload', 'restart:workers');
after('restart:workers', 'smoke:health');
after('deploy:failed', 'deploy:unlock');
