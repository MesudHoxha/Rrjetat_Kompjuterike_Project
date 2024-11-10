# TCP Socket Connection using PHP

Ky program përfshin një server dhe disa klientë që lidhen me të përmes protokollit TCP duke përdorur sockets në PHP. Programi lejon komunikimin mes serverit dhe klientëve, ku një klient mund të kenë qasje të plotë (write, read, execute) dhe të tjerët vetëm për lexim (read).

## Hapat për Ekzekutimin e Programit

### 1. Aktivizimi i PHP sockets
Për të ekzekutuar këtë program, duhet të keni **XAMPP** të instaluar dhe **PHP** të aktivizuar. 
1. Gjeni dhe hapni skedarin `php.ini`. Ai ndodhet në:
   - Për XAMPP: `C:\xampp\php\php.ini`
   - Për server të personalizuar: `C:\Program Files\PHP\php.ini`
3. Kërkoni për linjën që thotë `;extension=sockets` dhe hiqni **pikën e presjes** (`;`) para saj për ta aktivizuar rreshtin:
   extension=sockets
4. Pasi ta keni ndryshuar, ruani skedarin php.ini dhe ristartoni XAMPP

### 2. Komanda për Ekzekutimin e Serverit dhe Klientëve
Pasi të keni konfigurimin e nevojshëm, mund të ekzekutoni programin në komandën cmd.