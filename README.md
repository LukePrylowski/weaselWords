# Kto kręci?

Imprezowa gra w przeglądarce: PHP 8.1+, JavaScript i tekstowe pliki JSON. Bez bazy danych i instalowania zależności. Interfejs po polsku, dopasowany do telefonów.

## Uruchomienie

```sh
php -S 0.0.0.0:8000
```

Otwórz `http://localhost:8000`. Na telefonie w tej samej sieci otwórz `http://ADRES-IP-KOMPUTERA:8000`. Publicznie użyj hostingu obsługującego PHP 8.1+ i HTTPS; wbudowany serwer PHP służy do lokalnego sprawdzania.

## Przebieg

Pokój powstaje automatycznie w sesji przeglądarki. Wybierz kategorie, dodaj 3–20 graczy i rozpocznij rundę. Telefon przekazywany jest między graczami: odkrycie karty → przeczytanie → zakrycie → następna osoba. Dokładnie jeden gracz otrzymuje rolę OSZUST i podpowiedź, pozostali to samo hasło. Po ostatniej karcie START uruchamia stoper. Stop zatrzymuje czas; Nowa gra losuje kolejną rundę z tymi samymi ustawieniami. Można też wrócić do ustawień.

To gra na jednym przekazywanym urządzeniu, bez dołączania z osobnych telefonów. Kod pokoju jest identyfikatorem sesji, nie kodem zaproszenia. Sesja i aktualna runda przetrwają odświeżenie strony, dopóki nie wygasną według ustawień hostingu. Lista przygotowywanych graczy jest dodatkowo zapisywana w sessionStorage karty przeglądarki. Nie ma trwałego konta ani historii rozgrywek.

## Dodawanie kategorii i haseł

Każdy plik `data/categories/*.json` jest osobną kategorią. Nazwa pliku stanowi jej identyfikator. Pliki zapisuj w UTF-8, np. `sport.json`:

```json
{
  "name": "Sport",
  "emoji": "⚽",
  "words": [
    { "word": "Piłka nożna", "hint": "Jedenaście" },
    { "word": "Tenis", "hint": "Serwis" }
  ]
}
```

Dopisuj kolejne obiekty do `words`, rozdzielając je przecinkami. JSON nie obsługuje komentarzy ani przecinka po ostatnim elemencie. Każde hasło musi mieć niepuste `word` i `hint`. Nowa runda odczytuje pliki ponownie, bez restartu serwera. Po dodaniu nowej kategorii odśwież ustawienia, aby zobaczyć jej przycisk. Trwająca runda zachowuje wylosowane hasło. Usunięcie zaznaczonej kategorii wymaga odświeżenia ustawień. Błędne pliki i puste kategorie są pomijane, a nieprawidłowy JSON trafia do logu PHP. Przy publikowaniu zmian na serwerze najlepiej zastąpić cały plik gotową wersją.

Hasła są losowane ze wszystkich wpisów zaznaczonych kategorii, a oszust niezależnie spośród graczy. Każda nowa runda losuje też kolejność graczy; odświeżenie strony zachowuje kolejność trwającej rundy. Jeśli dostępne są różne hasła, bezpośrednio poprzednie nie powtórzy się.

Pliki kategorii zawierają ogólny słownik gry, nie sekrety. Mogą być publicznie dostępne na hostingu. Konkretna rola i hasło rundy są przechowywane w sesji PHP, a endpoint stanu ich nie ujawnia. Ta towarzyska gra zakłada uczciwe przekazywanie telefonu; nie jest zabezpieczeniem przed graczem analizującym ruch sieciowy na wspólnym urządzeniu.

Fonty są pobierane z Google Fonts, z lokalnym krojem zastępczym przy braku dostępu. Pozostałe zasoby są lokalne.

## Licznik rozegranych gier

Kafelek w `index.html` pobiera wspólny licznik wszystkich użytkowników z `weaselWords/stats.php` (względem adresu strony startowej, zgodnie z linkiem do gry). Strona startowa jest przeznaczona do umieszczenia poziom wyżej niż katalog gry `weaselWords`.

Runda jest doliczana po każdym poprawnym utworzeniu rundy przyciskiem „Nowa gra” — zarówno z ustawień, jak i z widoku gry. Stop i odświeżenie strony nie zwiększają licznika. Rundy są liczone także wtedy, gdy nie zostaną ukończone. Licznik zaczyna od zera po wdrożeniu; wcześniejsze gry nie były rejestrowane.

PHP musi mieć prawo zapisu w katalogu `data`. Plik `data/games-played.json` powstaje automatycznie, a blokada pliku chroni równoczesne aktualizacje na jednym serwerze. Zachowuj ten plik przy wdrożeniach i uwzględniaj go w kopiach zapasowych; nie jest śledzony przez Git. Gdy odczyt statystyk się nie powiedzie, kafelek ukrywa licznik.
