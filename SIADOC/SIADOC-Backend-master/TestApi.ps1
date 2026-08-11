$body = @{
    username = "cmd_drh"
    password = "password"
} | ConvertTo-Json

$login = Invoke-WebRequest -Uri "http://localhost:8080/api/auth/login" -Method Post -Body $body -ContentType "application/json" -SessionVariable mySession
if ($login.StatusCode -eq 200) {
    try {
        $mils = Invoke-RestMethod -Uri "http://localhost:8080/api/militaires" -WebSession $mySession
        Write-Host "Militaires Count for DRH:" $mils.Count
    } catch {
        Write-Host "Error fetching militaires:" $_.Exception.Message
    }
} else {
    Write-Host "Login Failed"
}

$bodyRmia = @{
    username = "cmd_rmia1"
    password = "password"
} | ConvertTo-Json

$loginRmia = Invoke-WebRequest -Uri "http://localhost:8080/api/auth/login" -Method Post -Body $bodyRmia -ContentType "application/json" -SessionVariable mySessionRmia
if ($loginRmia.StatusCode -eq 200) {
    try {
        $mils = Invoke-RestMethod -Uri "http://localhost:8080/api/militaires" -WebSession $mySessionRmia
        Write-Host "Militaires Count for RMIA1:" $mils.Count
    } catch {
        Write-Host "Error fetching militaires for RMIA1:" $_.Exception.Response.StatusCode
        Write-Host "Error Details:" $_.Exception.Message
    }
}
