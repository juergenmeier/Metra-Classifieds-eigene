# METRA Classifieds – Eigenentwicklung (Projektregeln)

Eigenständig entwickeltes WordPress-Kleinanzeigen-Plugin des Auftraggebers (J. Meier, Köln).
Der Plugin-Code liegt in `metra-classifieds-plugin/` (Server: `wp-content/plugins/metra-classifieds-plugin/`).
Aktueller Stand: Version 0.0726 (Kopfzeile `Version:` in `metra-classifieds-plugin/metra-classifieds.php`).

## Rolle dieses Repositorys (Entscheidung vom 16.10.2026)

- **Hauptentwicklung ist der AWPCP-Umbau** im Repository `juergenmeier/METRA-Classifieds` (Ordner `metra/`).
  Grund: vollständiger Funktionsumfang (Ablauf/Verlängerung, Suche, Moderation, Missbrauchsschutz,
  SEO, Import/Export) und gleiche Datenstruktur wie AWPCP auf vocas.de.
- Dieses Repository dient als **Vorlage für Oberfläche und Bedienung**. Übernommen werden in den Umbau:
  1. Benutzerbereich (Dashboard, Meine Anzeigen, Favoriten, Profil)
  2. Anzeige aufgeben / bearbeiten (Formular, Kategorie-Auswahl, Bild-Upload)
  3. Einzelanzeige, besonders die Handy-Ansicht
- Hier wird nur auf ausdrücklichen Auftrag weiterentwickelt. Programmcode wird nicht 1:1 in den Umbau
  kopiert, sondern dort mit dessen Daten (`metra_listing`, Meta-Felder des Umbaus) nachgebaut.

## Technische Eckdaten

- Beitragstyp `mc_listing`, Taxonomien `mc_category` und `mc_condition`.
- Meta-Felder: `mc_price`, `mc_price_type`, `mc_gallery_ids`, `mc_condition`, `_mc_paused`.
- Shortcodes mit Präfix `mc_` (z. B. `[mc_dashboard]`, `[mc_submit_form]`, `[mc_listing]`).
- Hauptlogik: `includes/class-mc-shortcodes.php`, Einzelansicht `includes/class-mc-listing-view.php`
  und `public/templates/single-mc_listing.php`, CSS `public/assets/css/mc-theme.css`,
  `mc-functional.css`, Design-Tokens `assets/master-template/metra-master.template.css`.
- Textdomain `metra-classifieds-plugin`, Sprachdateien `de_DE` (Du), `de_DE_formal` (Sie), `en_US`, `.pot`.
- Die eigenen Entwicklungsregeln stehen in `metra-classifieds-plugin/DEV_RULES.md` und gelten weiter.

## Zusammenarbeit (verbindlich)

- Der Auftraggeber ist kein Programmierer: Klartext, direkt, sachlich, auf Deutsch.
- Keine Höflichkeitsfloskeln, keine Kommentare zu eigenen Fehlern.
- Fragen im Text zuerst beantworten, erst danach fragen, ob umgesetzt werden soll.
- Vor jeder Umsetzung zusammenfassen, dann einzige Rückfrage: „Umsetzen?“.
  Kein automatisches Umsetzen ohne Bestätigung. Nach einem „Ja“ keine weiteren Rückfragen.
- Keine Dateiinhalte zeigen – nur Download und Pfad. Keine Dateien in den Chat senden.
- Code vor jeder Auslieferung prüfen (Syntax, Klammern, Schleifen, String-Verkettungen).
- Versionsnummer und Installationspfad immer nennen. Keine ZIP ohne ausdrücklichen Auftrag.
