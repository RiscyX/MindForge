# Test Engine Specification and Matching Implementation Plan

## Cel
Ez a dokumentum egysegesiti a quiz engine mukodeset, hogy a csapat minden tagja ugyanarra a logikara epitsen.

Fokusz:
- kerdestipusok egyertelmu definialasa
- pontozasi szabalyok rogzitese
- randomizalasi szabalyok rogzitese
- attempt lifecycle egységesitese
- uj `matching` (kotozgetos) kerdestipus bevezetesenek reszletes terve

## 1. Question Types (hivatalos)

Tamogatott kerdestipusok:
- `multiple_choice`
- `true_false`
- `text`
- `matching` (uj)

### 1.1 multiple_choice
- A kerdeshez tobb valaszopcio tartozik.
- A jelenlegi take UI radiogombot hasznal, tehat a felhasznalo egy opciot valaszt.
- A kerdes helyes, ha a valasztott answer `is_correct = true`.

### 1.2 true_false
- Fix 2 opcio: igaz/hamis.
- A kerdes helyes, ha a valasztott opcio a helyesnek jelolt.

### 1.3 text
- Szabad szoveges valasz.
- A helyesseg case-insensitive osszehasonlitassal tortenik az elfogadott helyes valaszokhoz.

### 1.4 matching (uj)
- Fogalom-jelentes (bal-jobb) parositas.
- A felhasznalo a bal oldali elemeket osszekoti a jobb oldali elemekkel.
- Ertekeles: **all-or-nothing** (ha barmelyik par hibas, a kerdes hibas).

## 2. Scoring (hivatalos)

A motor jelenlegi, hivatalos pontozasa:
- `correct_answers`: helyes kerdesek darabszama
- `total_questions`: aktiv kerdesek szama
- `score`: szazalekos eredmeny

Keplet:
`score = (correct_answers / total_questions) * 100`

Megjegyzesek:
- 2 tizedesre formazott szazalek tarolodik.
- A `matching` ugyanugy 1 kerdesnek szamit (helyes = 1, hibas = 0).

## 3. Randomization (hivatalos)

Jelenlegi szabaly:
- nincs randomizalas
- kerdesek sorrendje: `position ASC`, majd `id ASC`
- valaszok sorrendje: `position ASC`, majd `id ASC`

Kovetkezmeny:
- ugyanaz a quiz ugyanabban a sorrendben jelenik meg minden attemptben.

## 4. Attempt Lifecycle (hivatalos)

Statusz most timestamp + rekordletezes alapu:
- `in_progress`: attempt letezik es `finished_at = null`
- `finished`: submit utan `finished_at` kitoltve
- `cancelled`: jelenleg abort eseten fizikai torles (nincs kulon status mező)

## 5. Matching bevezetes - implementacios terv

## 5.1 Adatmodell strategia

Az uj `matching` tipust a meglevo `questions` + `answers` strukturara epitjuk.

Indok:
- kisebb regresszios kockazat
- ujrafelhasznalhato a translation infrastruktura
- AI/CRUD flow egyszerubb, mint teljesen uj tabla bevezetesevel

### 5.1.1 `answers` tabla bovites
Uj oszlopok:
- `match_side` (`left`|`right`, nullable)
- `match_group` (int, nullable)

Jelentes:
- azonos `match_group` + `left/right` ad egy osszetartozo part

### 5.1.2 `test_attempt_answers` tabla bovites
Uj oszlop:
- `user_answer_payload` (text/json, nullable)

Jelentes:
- matching valasz mentese (bal->jobb map)

### 5.1.3 `question_type` konzisztencia javitas
- DB default legyen `multiple_choice`.
- maradek `single_choice` ertekek migracioban atirasa `multiple_choice`-ra.

## 5.2 Migration terv

Javasolt uj migration fajl:
- `config/Migrations/YYYYMMDDHHMMSS_AddMatchingSupport.php`

Lepesek:
1. `answers` tablaban oszlopok hozzaadasa:
   - `match_side` string(10), null
   - `match_group` integer, null
2. indexek hozzaadasa:
   - (`question_id`, `match_side`, `position`)
   - (`question_id`, `match_group`)
3. `test_attempt_answers` tablaban oszlop hozzaadasa:
   - `user_answer_payload` text, null
4. `questions.question_type` default atallitas `multiple_choice`-ra
5. adattisztitas:
   - `UPDATE questions SET question_type = 'multiple_choice' WHERE question_type = 'single_choice'`

Rollback:
- uj oszlopok es indexek torlese
- default visszaallitas (ha szukseges)

## 5.3 Model/Entity/Table valtozasok

### 5.3.1 `src/Model/Entity/Question.php`
- uj konstans: `TYPE_MATCHING = 'matching'`

### 5.3.2 `src/Model/Entity/Answer.php`
- `_accessible` bovites: `match_side`, `match_group`

### 5.3.3 `src/Model/Entity/TestAttemptAnswer.php`
- `_accessible` bovites: `user_answer_payload`

### 5.3.4 `src/Model/Table/QuestionsTable.php`
- `question_type` validacio explicit inList:
  - `multiple_choice`, `true_false`, `text`, `matching`
- szabalyok tipusfuggoen:
  - `multiple_choice`/`true_false`: min. 1 helyes answer
  - `text`: min. 1 helyes text answer
  - `matching`:
    - legalabb 1 par
    - minden `match_group`-ban pontosan 1 `left` es 1 `right`
    - bal es jobb elemek darabszama egyezzen

### 5.3.5 `src/Model/Table/AnswersTable.php`
- `match_side` inList (`left`,`right`) ha nem ures
- `match_group` non-negative integer ha nem ures
- opcionais kereszt-validacio:
  - ha az egyik kitoltott, a masik is kotelezo

## 5.4 Builder UI/JS valtozasok (add/edit)

Erintett fajlok:
- `templates/Tests/add.php`
- `templates/Tests/edit.php`
- `webroot/js/tests_add.js`

Lepesek:
1. Tipus selector bovites `matching` opcioval.
2. `changeQuestionType()` uj `matching` branch.
3. Matching editor UI:
   - soronkent egy par: bal oldali input + jobb oldali input
   - hidden mezok:
     - `match_side` (`left`/`right`)
     - `match_group` (azonos integer)
4. Pair management:
   - `Add Pair`
   - `Remove Pair`
5. Szerkesztes (edit) preload:
   - answer adatokbol parok visszaepitese `match_group` alapjan
6. Validation UI oldalon:
   - legalabb 1 par
   - ures parfelek tiltasa

## 5.5 Quiz futtatas es submit (web)

Erintett fajlok:
- `templates/Tests/take.php`
- `src/Controller/TestsController.php`

Lepesek:
1. `take.php` matching render:
   - bal oldali lista
   - jobb oldali lista
   - valasztas UI (drag-drop vagy select)
2. Javasolt submit payload:
   - `answers[QUESTION_ID][pairs][LEFT_ANSWER_ID] = RIGHT_ANSWER_ID`
3. `submit()` matching ag:
   - input parse
   - one-to-one ellenorzes
   - helyesseg: `match_group` egyezes minden bal elemre
4. mentes `test_attempt_answers`-ba:
   - `answer_id = null`
   - `user_answer_payload = json_encode(pairs)`
   - `is_correct = true/false`

## 5.6 Review/result (web)

Erintett fajlok:
- `templates/Tests/review.php`
- `templates/Tests/result.php`

Lepesek:
1. Review matching blokk:
   - felhasznalo parositasainak listaja
   - helyes/hibas jeloles soronkent
   - helyes parok megjelenitese
2. Result oldalon aggregalt score logika valtozatlan.

## 5.7 API valtozasok

Erintett fajlok:
- `src/Controller/Api/AttemptsController.php`
- `src/Controller/Api/TestsController.php`

Lepesek:
1. API `view` payload matchinghez:
   - kerdes+valasz tartalom atadas
   - helyesseghez tartozo map (`match_group`) elrejtese kliens oldali submit elott
2. API `submit` matching tamogatas:
   - `answers[qid].pairs` parse
   - ugyanaz a kiertékeles mint weben
   - `user_answer_payload` mentes
3. API `review` matching reszletek:
   - user pair-ek
   - helyes pair-ek
   - kerdes-szintu `is_correct`

## 5.8 AI generate/translate valtozasok

Erintett fajl:
- `src/Controller/TestsController.php`

Lepesek:
1. `generateWithAi` prompt frissites:
   - engedelyezett tipusokhoz `matching` hozzadasa
   - vart kimeneti formatumban `pairs` strukturat kerunk
2. parser/frissites:
   - `pairs` -> `answers(match_side/match_group)` transzformacio
3. `translateWithAi` payload frissites:
   - matching elemek nyelvi tartalmanak kuldese/fogadasa

## 5.9 Tesztelesi terv

### Unit/Table tesztek
- `QuestionsTable` tipusfuggo validaciok
- `AnswersTable` matching mezo validaciok

### Controller/API tesztek
- web submit matching helyes/hibas
- API submit matching helyes/hibas
- review payload ellenorzes

### Regresszio
- `multiple_choice`, `true_false`, `text` valtozatlanul mukodik

## 5.10 Bevezetesi sorrend (ajanlott)

1. Migration + model validaciok
2. Builder UI + save/edit
3. Web take/submit/review
4. API submit/review/view
5. AI flow frissites
6. Tesztek + bugfix + release

## 6. Elfogadasi kriteriumok

Kesz, ha:
- a csapat ugyanarra a kerdes-tipus modellre epit
- a scoring szabalyok egyertelmuek es egyeznek a futo koddal
- random szabaly egyertelmuen dokumentalt
- attempt lifecycle nem ertelmezheto felre
- matching vegig mukodik create -> take -> submit -> review utvonalon

## 7. Checklist

- [x] Doksi kesz
- [x] Types rogzitve
- [x] Scoring rogzitve
- [x] Random rogzitve
- [x] Matching terv rogzitve (migration + backend + frontend + API + test)
