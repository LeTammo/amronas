# Watchlist

Our small web app to save movies and series we want to watch together, and to find something good to watch for a movie night.

- Create watchlists of movies and/or series. Search and add entries via IMDb integration.
- Shows which providers stream your watchlist entries.
- Invite people to your lists.

Built with Symfony 8.1, PHP 8.5, Doctrine ORM with SQLite, Twig, AssetMapper, Stimulus and Turbo.

## Requirements

- PHP 8.4+ (8.5 recommended) with the `pdo_sqlite`, `ctype` and `iconv` extensions
- Composer
- For the background worker in production: Docker (or a cron job, see below)

Enable SQLite in your `php.ini`:

```ini
extension=pdo_sqlite
extension=sqlite3
```

## Production

1. Point a web server (e.g. nginx + PHP-FPM) at `public/`.
2. Create `.env.local` with at least:

   ```dotenv
   APP_ENV=prod
   APP_SECRET=a-long-random-string
   MOTN_API_KEY=your-key
   TMDB_API_KEY=your-tmdb-key
   OMDB_API_KEY=your-omdb-key
   REGISTRATION_ENABLED=0
   ```

3. Install and set up:

   ```bash
   composer install --no-dev --optimize-autoloader
   php bin/console doctrine:migrations:migrate -n
   php bin/console asset-map:compile
   php bin/console cache:clear
   ```

4. Start the background worker with Docker. It only runs `messenger:consume` in a `php:8.5-cli` container and shares the project folder (and the SQLite database in `var/`):

   ```bash
   docker compose up -d
   ```

   Set `WORKER_UID`/`WORKER_GID` if your web server doesn't run as `www-data` (33). The container keeps its cache and logs in `/tmp`, so it doesn't interfere with the web server's `var/cache`.
   Without Docker, a cron job works too:

   ```cron
   * * * * * cd /path/to/watchlist && php bin/console messenger:consume async --time-limit=55 >/dev/null 2>&1
   ```

After each deployment, run `php bin/console messenger:stop-workers` so the worker picks up the new code.

The database is the file `var/data_prod.db`. Back it up like any other file.

### Making someone an admin

Admins can edit all users on `/admin/users`. To make the first admin:

```bash
php bin/console app:user:admin username
```

## Development

```bash
composer install
php bin/console doctrine:migrations:migrate -n
php bin/console doctrine:fixtures:load -n
```

The fixtures create example users, lists and titles. The users and their password are in `src/DataFixtures/AppFixtures.php`. `robin` is an admin, and `erik` hasn't done the first-login setup yet.

Start the app with the [Symfony CLI](https://symfony.com/download). It also starts the background worker and restarts it when code changes (see `.symfony.local.yaml`):

```bash
symfony serve
```

Or without the Symfony CLI, in two terminals:

```bash
php -S 127.0.0.1:8000 -t public
```

```bash
php bin/console messenger:consume async -vv
```

Then open http://127.0.0.1:8000.

### External APIs and saved responses

| What | API | Cost |
| --- | --- | --- |
| Search | IMDb suggestion endpoint (`v3.sg.media-imdb.com`) | free, no key, cached for 1 day |
| Details (genres, runtime, rating, plot, seasons) and streaming offers | [Movie of the Night – Streaming Availability API](https://docs.movieofthenight.com/), `MOTN_API_KEY` | free plan: 1,000 requests/month, one request per added title |
| Streaming offers of services the API above doesn't cover (ARD, ZDF, ARTE, Joyn, MagentaTV, Sky Go, discovery+, YouTube, Rakuten TV, VIDEOBUSTER, maxdome) and subscriptions it misses | [TMDB](https://developer.themoviedb.org/reference/movie-watch-providers) (watch providers by JustWatch), `TMDB_API_KEY` | free, no monthly quota, two requests per added title |
| Episode runtime of series as IMDb shows it | [OMDb API](https://www.omdbapi.com), `OMDB_API_KEY` (optional; without it the median episode of season 1 from TMDB is used) | free key: 1,000 requests/day, one request per added series |

To save the monthly quota during development, API responses are saved to `tests/Fixtures/api/` and reused (`API_RECORDINGS` in `.env`):

- `dev`: `record`, which reuses saved responses and saves new successful ones.
- `test`: `replay`, which only uses saved responses and never touches the network.
- `prod`: `off`.

A real response for "Inception" (`tt1375666`, country: "Germany") is saved, so the whole flow can be tried without using the quota. Search "inception" and add it.

Requests to the streaming API are also capped per calendar month (`STREAMING_API_MONTHLY_BUDGET`, default 900), just like the API's own quota, which resets at the start of each month. The counter is stored in the database, so the web server and the worker share it and it survives restarts. No request is sent without a key. When everyone on a list is in the same country, only that country is requested, which keeps responses small.

Put the keys in `.env.local`:

```dotenv
MOTN_API_KEY=your-key
TMDB_API_KEY=your-tmdb-key
OMDB_API_KEY=your-omdb-key
```

To fetch the TMDB offers (and OMDb runtimes) for titles that are already on lists (e.g. after setting the key for the first time), queue them for the worker:

```bash
php bin/console app:refresh-providers
```

### Tests

```bash
php bin/phpunit
```
