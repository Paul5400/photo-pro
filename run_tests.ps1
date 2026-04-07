<#
  Tests integration Photo-Pro - service-galeries
  Usage: powershell -ExecutionPolicy Bypass -File run_tests.ps1
#>
$T   = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJwaG90b3Byby1hdXRoIiwiaWF0IjoxNzc1NTU0NzU4LCJleHAiOjE3NzYxNTk1NTgsInN1YiI6ImFiMTAyN2ZjLWI0MzYtNGFhNi05MjJjLTNkNWI3ZGFiMjdkMiIsInJvbGUiOiJwaG90b2dyYXBoZSIsInVzZXIiOnsiaWQiOiJhYjEwMjdmYy1iNDM2LTRhYTYtOTIyYy0zZDViN2RhYjI3ZDIiLCJwc2V1ZG8iOiJqZWFuZHVwb250IiwiZW1haWwiOiJqZWFuQHRlc3QuY29tIn19.jayQLi3LlcL9YEzg2uBMvo1uidGfV6SZpxjDUvuuAvo"
$F   = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJoYWNrZXIiLCJleHAiOjk5OTk5OTk5OTl9.AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA"
$B   = "http://localhost:8083"
$GW  = "http://localhost:8081"
$PUB  = "aaaaaaaa-0000-0000-0000-000000000001"
$PRIV = "bbbbbbbb-1111-0000-0000-000000000001"
$BRO  = "4b7bc9a3-99b3-4525-bb20-f8d04d93c34c"
$PH   = "bbbbbbbb-0000-0000-0000-000000000001"

$pass = 0; $fail = 0

function TC {
    param($id, $desc, $exp, [hashtable]$p)
    try {
        $r   = Invoke-WebRequest -UseBasicParsing @p
        $got = [int]$r.StatusCode
    } catch {
        $got = [int]$_.Exception.Response.StatusCode
    }
    if ($got -eq $exp) {
        $script:pass++
        Write-Host "  PASS [$id] $desc" -ForegroundColor Green
    } else {
        $script:fail++
        Write-Host "  FAIL [$id] $desc  (attendu $exp, obtenu $got)" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "============================================================"
Write-Host "   COMPTE RENDU DES TESTS - Photo-Pro service-galeries"
Write-Host "============================================================"

# ---- BLOC 1 : JWT direct sur service-galeries :8083 ----------------------
Write-Host ""
Write-Host "[BLOC 1] JWT service-galeries direct :8083"

TC "T01" "sans token -> 401" 401 @{ Uri="$B/galeries"; Method="GET" }
TC "T02" "token forge -> 401" 401 @{ Uri="$B/galeries"; Method="GET"; Headers=@{Authorization="Bearer $F"} }
TC "T03" "token valide -> 200" 200 @{ Uri="$B/galeries"; Method="GET"; Headers=@{Authorization="Bearer $T"} }

# ---- BLOC 2 : JWT via gateway backoffice :8081 ---------------------------
Write-Host ""
Write-Host "[BLOC 2] JWT gateway backoffice :8081"

TC "T04" "sans token -> 401" 401 @{ Uri="$GW/galeries"; Method="GET" }
TC "T05" "token forge -> 401" 401 @{ Uri="$GW/galeries"; Method="GET"; Headers=@{Authorization="Bearer $F"} }
TC "T06" "token valide -> 200" 200 @{ Uri="$GW/galeries"; Method="GET"; Headers=@{Authorization="Bearer $T"} }

# ---- BLOC 3 : Liste des galeries -----------------------------------------
Write-Host ""
Write-Host "[BLOC 3] Liste galeries"

TC "T07" "liste galeries -> 200" 200 @{ Uri="$B/galeries"; Method="GET"; Headers=@{Authorization="Bearer $T"} }

# ---- BLOC 4 : GET galerie publique ---------------------------------------
Write-Host ""
Write-Host "[BLOC 4] GET galerie publique"

TC "T08" "publiee -> 200"   200 @{ Uri="$B/galeries/$PUB"; Method="GET" }
TC "T09" "brouillon -> 404" 404 @{ Uri="$B/galeries/$BRO"; Method="GET" }

# ---- BLOC 5 : GET galerie privee -----------------------------------------
Write-Host ""
Write-Host "[BLOC 5] GET galerie privee"

TC "T10" "sans code -> 403"    403 @{ Uri="$B/galeries/$PRIV"; Method="GET" }
TC "T11" "mauvais code -> 403" 403 @{ Uri="$B/galeries/${PRIV}?code_acces=MAUVAIS"; Method="GET" }
TC "T12" "bon code -> 200"     200 @{ Uri="$B/galeries/${PRIV}?code_acces=CODE123"; Method="GET" }

# ---- BLOC 6 : Propriete / ownership -------------------------------------
Write-Host ""
Write-Host "[BLOC 6] Propriete galerie"

$body13 = '{"photo_id":"cccccccc-0000-0000-0000-000000000001","ordre":1}'
TC "T13" "addPhoto galerie etrangere -> 500" 500 @{
    Uri     = "$B/galeries/$PUB/photos"
    Method  = "PATCH"
    Headers = @{ Authorization="Bearer $T"; "Content-Type"="application/json" }
    Body    = $body13
}

# ---- BLOC 7 : Commentaires -----------------------------------------------
Write-Host ""
Write-Host "[BLOC 7] Commentaires"

TC "T14" "brouillon -> 403" 403 @{
    Uri         = "$B/galeries/$BRO/photos/$PH/commentaires"
    Method      = "POST"
    ContentType = "application/json"
    Body        = '{"auteur":"X","contenu":"Hello"}'
}

TC "T15" "publiee -> 201" 201 @{
    Uri         = "$B/galeries/$PUB/photos/$PH/commentaires"
    Method      = "POST"
    ContentType = "application/json"
    Body        = '{"auteur":"Testeur","contenu":"Super"}'
}

TC "T16" "contenu vide -> 400" 400 @{
    Uri         = "$B/galeries/$PUB/photos/$PH/commentaires"
    Method      = "POST"
    ContentType = "application/json"
    Body        = '{"auteur":"X","contenu":""}'
}

TC "T17" "privee sans code -> 403" 403 @{
    Uri         = "$B/galeries/$PRIV/photos/$PH/commentaires"
    Method      = "POST"
    ContentType = "application/json"
    Body        = '{"auteur":"X","contenu":"Essai"}'
}

TC "T18" "privee bon code -> 201" 201 @{
    Uri         = "$B/galeries/$PRIV/photos/$PH/commentaires"
    Method      = "POST"
    ContentType = "application/json"
    Body        = '{"auteur":"Client","contenu":"Bravo","code_acces":"CODE123"}'
}

# ---- Resultat ------------------------------------------------------------
$total = $pass + $fail
Write-Host ""
Write-Host "============================================================"
Write-Host "  TOTAL : $total tests | PASS: $pass | FAIL: $fail" -ForegroundColor $(if ($fail -eq 0) {'Green'} else {'Yellow'})
Write-Host "============================================================"
Write-Host ""
