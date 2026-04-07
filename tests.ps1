$BASE = "http://localhost:8083"
$GW   = "http://localhost:8081"
$T    = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJwaG90b3Byby1hdXRoIiwiaWF0IjoxNzc1NTU0NzU4LCJleHAiOjE3NzYxNTk1NTgsInN1YiI6ImFiMTAyN2ZjLWI0MzYtNGFhNi05MjJjLTNkNWI3ZGFiMjdkMiIsInJvbGUiOiJwaG90b2dyYXBoZSIsInVzZXIiOnsiaWQiOiJhYjEwMjdmYy1iNDM2LTRhYTYtOTIyYy0zZDViN2RhYjI3ZDIiLCJwc2V1ZG8iOiJqZWFuZHVwb250IiwiZW1haWwiOiJqZWFuQHRlc3QuY29tIn19.jayQLi3LlcL9YEzg2uBMvo1uidGfV6SZpxjDUvuuAvo"
$F    = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJoYWNrZXIiLCJleHAiOjk5OTk5OTk5OTl9.AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA"
$GPUB  = "aaaaaaaa-0000-0000-0000-000000000001"
$GPRIV = "bbbbbbbb-1111-0000-0000-000000000001"
$GBROU = "4b7bc9a3-99b3-4525-bb20-f8d04d93c34c"
$PHOTOID = "bbbbbbbb-0000-0000-0000-000000000001"
$pass=0; $fail=0; $log=@()

function TC($id,$desc,$exp,$url,$method="GET",$hdrs=@{},$body=$null,$ct=$null) {
    try {
        $params = @{ Uri=$url; Method=$method; ErrorAction="Stop" }
        if ($hdrs.Count -gt 0) { $params.Headers = $hdrs }
        if ($body) { $params.Body = $body }
        if ($ct)   { $params.ContentType = $ct }
        $resp = Invoke-WebRequest @params
        $got  = [int]$resp.StatusCode
    } catch {
        $got = $_.Exception.Response.StatusCode.value__
    }
    if ($got -eq $exp) { $script:pass++; $script:log+="  PASS [$id] $desc" }
    else { $script:fail++; $script:log+="  FAIL [$id] $desc -- attendu $exp got $got" }
}

$log += ""; $log += "[BLOC 1] JWT AuthMiddleware service-galeries (:8083)"
TC "T01" "sans token -> 401" 401 "$BASE/galeries"
TC "T02" "token forge -> 401" 401 "$BASE/galeries" "GET" @{Authorization="Bearer $F"}
TC "T03" "token valide -> 200" 200 "$BASE/galeries" "GET" @{Authorization="Bearer $T"}

$log += ""; $log += "[BLOC 2] JWT gateway backoffice (:8081)"
TC "T04" "sans token -> 401" 401 "$GW/galeries"
TC "T05" "token forge -> 401" 401 "$GW/galeries" "GET" @{Authorization="Bearer $F"}
TC "T06" "token valide via gateway -> 200" 200 "$GW/galeries" "GET" @{Authorization="Bearer $T"}

$log += ""; $log += "[BLOC 3] GET /galeries"
TC "T07" "liste galeries photographe -> 200" 200 "$BASE/galeries" "GET" @{Authorization="Bearer $T"}

$log += ""; $log += "[BLOC 4] GET /galeries/{id} publique"
TC "T08" "galerie publiee -> 200" 200 "$BASE/galeries/$GPUB"
TC "T09" "galerie brouillon -> 404" 404 "$BASE/galeries/$GBROU"

$log += ""; $log += "[BLOC 5] GET /galeries/{id} privee"
TC "T10" "privee sans code -> 403" 403 "$BASE/galeries/$GPRIV"
TC "T11" "privee mauvais code -> 403" 403 "$BASE/galeries/${GPRIV}?code_acces=MAUVAIS"
TC "T12" "privee bon code -> 200" 200 "$BASE/galeries/${GPRIV}?code_acces=CODE123"

$log += ""; $log += "[BLOC 6] Ownership"
TC "T13" "addPhoto galerie etrangere -> 500" 500 "$BASE/galeries/$GPUB/photos" "PATCH" @{Authorization="Bearer $T";"Content-Type"="application/json"} '{"photo_id":"cccccccc-0000-0000-0000-000000000001","ordre":1}' "application/json"

$log += ""; $log += "[BLOC 7] Commentaires"
TC "T14" "galerie brouillon -> 403" 403 "$BASE/galeries/$GBROU/photos/$PHOTOID/commentaires" "POST" @{} '{"auteur":"X","contenu":"Hello"}' "application/json"
TC "T15" "galerie publiee -> 201" 201 "$BASE/galeries/$GPUB/photos/$PHOTOID/commentaires" "POST" @{} '{"auteur":"Testeur","contenu":"Super !"}' "application/json"
TC "T16" "contenu vide -> 400" 400 "$BASE/galeries/$GPUB/photos/$PHOTOID/commentaires" "POST" @{} '{"auteur":"X","contenu":""}' "application/json"
TC "T17" "privee sans code -> 403" 403 "$BASE/galeries/$GPRIV/photos/$PHOTOID/commentaires" "POST" @{} '{"auteur":"X","contenu":"Essai"}' "application/json"
TC "T18" "privee bon code -> 201" 201 "$BASE/galeries/$GPRIV/photos/$PHOTOID/commentaires" "POST" @{} '{"auteur":"Client","contenu":"Magnifique !","code_acces":"CODE123"}' "application/json"

$total = $pass+$fail
Write-Host ""
Write-Host "============================================================"
Write-Host "   COMPTE RENDU DES TESTS - Photo-Pro service-galeries"
Write-Host "============================================================"
$log | ForEach-Object { Write-Host $_ }
Write-Host ""
Write-Host "  TOTAL : $total tests | PASS: $pass | FAIL: $fail"
Write-Host "============================================================"