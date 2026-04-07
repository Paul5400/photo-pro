@echo off
setlocal EnableDelayedExpansion

set T=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJwaG90b3Byby1hdXRoIiwiaWF0IjoxNzc1NTU0NzU4LCJleHAiOjE3NzYxNTk1NTgsInN1YiI6ImFiMTAyN2ZjLWI0MzYtNGFhNi05MjJjLTNkNWI3ZGFiMjdkMiIsInJvbGUiOiJwaG90b2dyYXBoZSIsInVzZXIiOnsiaWQiOiJhYjEwMjdmYy1iNDM2LTRhYTYtOTIyYy0zZDViN2RhYjI3ZDIiLCJwc2V1ZG8iOiJqZWFuZHVwb250IiwiZW1haWwiOiJqZWFuQHRlc3QuY29tIn19.jayQLi3LlcL9YEzg2uBMvo1uidGfV6SZpxjDUvuuAvo
set F=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJoYWNrZXIiLCJleHAiOjk5OTk5OTk5OTl9.AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
set BASE=http://localhost:8083
set GW=http://localhost:8081
set GPUB=aaaaaaaa-0000-0000-0000-000000000001
set GPRIV=bbbbbbbb-1111-0000-0000-000000000001
set GBROU=4b7bc9a3-99b3-4525-bb20-f8d04d93c34c
set PHID=bbbbbbbb-0000-0000-0000-000000000001
set PASS=0
set FAIL=0

echo.
echo ============================================================
echo    COMPTE RENDU DES TESTS - Photo-Pro service-galeries
echo ============================================================

echo.
echo [BLOC 1] JWT service-galeries direct :8083

for /f %%s in ('curl.exe -s -o NUL -w "%%{http_code}" %BASE%/galeries') do set GOT=%%s
call :check T01 "sans token -> 401" 401 !GOT!

for /f %%s in ('curl.exe -s -o NUL -w "%%{http_code}" -H "Authorization: Bearer %F%" %BASE%/galeries') do set GOT=%%s
call :check T02 "token forge -> 401" 401 !GOT!

for /f %%s in ('curl.exe -s -o NUL -w "%%{http_code}" -H "Authorization: Bearer %T%" %BASE%/galeries') do set GOT=%%s
call :check T03 "token valide -> 200" 200 !GOT!

echo.
echo [BLOC 2] JWT gateway backoffice :8081

for /f %%s in ('curl.exe -s -o NUL -w "%%{http_code}" %GW%/galeries') do set GOT=%%s
call :check T04 "sans token -> 401" 401 !GOT!

for /f %%s in ('curl.exe -s -o NUL -w "%%{http_code}" -H "Authorization: Bearer %F%" %GW%/galeries') do set GOT=%%s
call :check T05 "token forge -> 401" 401 !GOT!

for /f %%s in ('curl.exe -s -o NUL -w "%%{http_code}" -H "Authorization: Bearer %T%" %GW%/galeries') do set GOT=%%s
call :check T06 "token valide -> 200" 200 !GOT!

echo.
echo [BLOC 3] Liste galeries

for /f %%s in ('curl.exe -s -o NUL -w "%%{http_code}" -H "Authorization: Bearer %T%" %BASE%/galeries') do set GOT=%%s
call :check T07 "liste galeries -> 200" 200 !GOT!

echo.
echo [BLOC 4] GET galerie publique

for /f %%s in ('curl.exe -s -o NUL -w "%%{http_code}" %BASE%/galeries/%GPUB%') do set GOT=%%s
call :check T08 "publiee -> 200" 200 !GOT!

for /f %%s in ('curl.exe -s -o NUL -w "%%{http_code}" %BASE%/galeries/%GBROU%') do set GOT=%%s
call :check T09 "brouillon -> 404" 404 !GOT!

echo.
echo [BLOC 5] GET galerie privee

for /f %%s in ('curl.exe -s -o NUL -w "%%{http_code}" %BASE%/galeries/%GPRIV%') do set GOT=%%s
call :check T10 "sans code -> 403" 403 !GOT!

for /f %%s in ('curl.exe -s -o NUL -w "%%{http_code}" "%BASE%/galeries/%GPRIV%?code_acces=MAUVAIS"') do set GOT=%%s
call :check T11 "mauvais code -> 403" 403 !GOT!

for /f %%s in ('curl.exe -s -o NUL -w "%%{http_code}" "%BASE%/galeries/%GPRIV%?code_acces=CODE123"') do set GOT=%%s
call :check T12 "bon code -> 200" 200 !GOT!

echo.
echo [BLOC 6] Propriete galerie

for /f %%s in ('curl.exe -s -o NUL -w "%%{http_code}" -X PATCH -H "Authorization: Bearer %T%" -H "Content-Type: application/json" -d "{\"photo_id\":\"cccccccc-0000-0000-0000-000000000001\",\"ordre\":1}" %BASE%/galeries/%GPUB%/photos') do set GOT=%%s
call :check T13 "addPhoto galerie etrangere -> 500" 500 !GOT!

echo.
echo [BLOC 7] Commentaires

for /f %%s in ('curl.exe -s -o NUL -w "%%{http_code}" -X POST -H "Content-Type: application/json" -d "{\"auteur\":\"X\",\"contenu\":\"Hello\"}" %BASE%/galeries/%GBROU%/photos/%PHID%/commentaires') do set GOT=%%s
call :check T14 "brouillon -> 403" 403 !GOT!

for /f %%s in ('curl.exe -s -o NUL -w "%%{http_code}" -X POST -H "Content-Type: application/json" -d "{\"auteur\":\"Testeur\",\"contenu\":\"Super !\"}" %BASE%/galeries/%GPUB%/photos/%PHID%/commentaires') do set GOT=%%s
call :check T15 "publiee -> 201" 201 !GOT!

for /f %%s in ('curl.exe -s -o NUL -w "%%{http_code}" -X POST -H "Content-Type: application/json" -d "{\"auteur\":\"X\",\"contenu\":\"\"}" %BASE%/galeries/%GPUB%/photos/%PHID%/commentaires') do set GOT=%%s
call :check T16 "contenu vide -> 400" 400 !GOT!

for /f %%s in ('curl.exe -s -o NUL -w "%%{http_code}" -X POST -H "Content-Type: application/json" -d "{\"auteur\":\"X\",\"contenu\":\"Essai\"}" %BASE%/galeries/%GPRIV%/photos/%PHID%/commentaires') do set GOT=%%s
call :check T17 "privee sans code -> 403" 403 !GOT!

for /f %%s in ('curl.exe -s -o NUL -w "%%{http_code}" -X POST -H "Content-Type: application/json" -d "{\"auteur\":\"Client\",\"contenu\":\"Bravo\",\"code_acces\":\"CODE123\"}" %BASE%/galeries/%GPRIV%/photos/%PHID%/commentaires') do set GOT=%%s
call :check T18 "privee bon code -> 201" 201 !GOT!

echo.
echo ============================================================
echo   TOTAL : %PASS% PASS / %FAIL% FAIL
echo ============================================================
echo.
goto :eof

:check
set ID=%1
set DESC=%~2
set EXP=%3
set ACT=%4
if "%ACT%"=="%EXP%" (
    echo   PASS [%ID%] %DESC%
    set /a PASS+=1
) else (
    echo   FAIL [%ID%] %DESC%  ^(attendu %EXP%, obtenu %ACT%^)
    set /a FAIL+=1
)
goto :eof
