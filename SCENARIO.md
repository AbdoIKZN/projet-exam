# EventHub Pro - Scenario de test bout-en-bout

## Contexte du test

Ce document valide la Partie 5 de l'examen : integration complete entre la base de donnees, les inscriptions, les emails, les PDFs et l'interface AJAX.

- Application testee : `http://127.0.0.1/MVPexam/index.php`
- Base de donnees : `eventhub_db`
- Configuration email : SMTP Gmail avec PHPMailer
- Organisateur du test : adresse Gmail du professeur utilisee dans le champ `organizer_email`
- Evenement de test : `DevFest Marrakech 2026`
- Capacite : `5` places

## Scenario principal

| Etape | Action realisee | Resultat attendu | Resultat obtenu |
|---|---|---|---|
| 1 | Un organisateur cree `DevFest Marrakech 2026` avec une capacite de 5 places. | L'evenement est cree en base de donnees et apparait dans la liste AJAX. | OK - l'evenement apparait dans la page d'accueil avec `0 / 5` inscriptions au depart. |
| 2 | Quatre utilisateurs differents s'inscrivent un par un. | Quatre inscriptions sont ajoutees, quatre emails de confirmation sont envoyes, le compteur est mis a jour. | OK - les inscriptions sont enregistrees, les compteurs AJAX progressent, les emails de confirmation sont envoyes. |
| 3 | Le quatrieme inscrit fait atteindre 80% de capacite. | Un email d'alerte est envoye automatiquement a l'organisateur avec le rapport PDF en piece jointe. | OK - l'email d'alerte 80% est envoye a l'adresse Gmail du professeur avec un rapport PDF attache. |
| 4 | Un cinquieme utilisateur s'inscrit. | L'evenement devient complet, la capacite passe a `5 / 5`, le bouton d'inscription est desactive. | OK - l'interface indique l'evenement complet et bloque les nouvelles inscriptions. |
| 5 | L'organisateur telecharge le rapport PDF. | Un rapport PDF multi-pages est genere avec resume, liste des inscrits et graphique. | OK - le rapport PDF est genere depuis `pdf/report.php` et contient les statistiques de l'evenement. |
| 6 | Un inscrit utilise son lien de desinscription recu par email. | L'inscription est annulee, une place est liberee, l'interface se met a jour. | OK - le lien de desinscription annule l'inscription et la capacite disponible est recalculee. |

## Verification technique

- Les interactions avec MySQL passent par PDO et des requetes preparees.
- L'inscription utilise un token unique pour le ticket PDF et le lien de desinscription.
- L'alerte 80% est controlee par le champ `events.alert_sent` pour eviter les doublons.
- Les emails sont envoyes via PHPMailer et Gmail SMTP.
- Le ticket PDF est genere par `pdf/ticket.php`.
- Le rapport organisateur est genere par `pdf/report.php`.
- La page d'accueil utilise `fetch()` pour charger les evenements et mettre a jour les compteurs sans rechargement complet.
- Le dashboard utilise `api/stats.php` pour afficher les statistiques en temps reel.

## Pieces justificatives a fournir

- `pdf/samples/ticket_example.pdf` : exemple de ticket d'inscription.
- `pdf/samples/report_example.pdf` : exemple de rapport organisateur.
- `screenshots/` : captures de la liste des evenements, du dashboard, et/ou de l'email recu.

## Conclusion

Le flux complet est fonctionnel : creation d'evenement, inscription, envoi d'email de confirmation, generation de ticket PDF, detection du seuil 80%, alerte organisateur avec PDF joint, evenement complet, rapport PDF et desinscription.
