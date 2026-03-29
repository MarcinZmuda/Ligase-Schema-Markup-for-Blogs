# Wspolpraca

Dziekuje za zainteresowanie rozwojem Ligase! Ponizej znajdziesz wskazowki jak wniesc swoj wklad.

## Zgaszanie bledow

1. Sprawdz czy blad nie zostal juz zgloszony w [Issues](../../issues)
2. Utworz nowy issue z opisem:
   - Wersja WordPress i PHP
   - Kroki do odtworzenia bledu
   - Oczekiwane vs rzeczywiste zachowanie
   - Logi z `wp-content/uploads/ligase-logs/` (jesli dostepne)

## Pull Requesty

1. Forkuj repozytorium
2. Utworz branch z opisowa nazwa: `feature/nowa-funkcja` lub `fix/opis-bledu`
3. Pisz kod zgodny z [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
4. Dodaj testy jednostkowe dla nowej funkcjonalnosci
5. Upewnij sie ze testy przechodza: `./vendor/bin/phpunit`
6. Utworz Pull Request z opisem zmian

## Standardy kodu

- PHP 8.0+ (typed properties, match expression, constructor promotion)
- WordPress Coding Standards (WPCS)
- Prefix `ligase_` lub `Ligase_` dla wszystkich funkcji, klas, hookow
- Sanityzacja: `sanitize_text_field()`, `esc_url_raw()`, `absint()`
- Escape: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- Nonce verification na wszystkich formularzach i AJAX
- Capability checks (`current_user_can()`)

## Struktura commitow

```
typ: krotki opis

Dluzszy opis jesli potrzebny.
```

Typy: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`

## Uruchamianie testow

```bash
composer install
./vendor/bin/phpunit
```

## Licencja

Wnosac zmiany, zgadzasz sie na ich licencjonowanie na GPL v2 lub pozniejszej.
