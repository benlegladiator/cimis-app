$bodyRmia = @{
    username = "cmd_rmia1"
    password = "password"
} | ConvertTo-Json

try {
    $loginRmia = Invoke-WebRequest -Uri "http://localhost:8080/api/auth/login" -Method Post -Body $bodyRmia -ContentType "application/json" -SessionVariable mySessionRmia
    
    $mils = Invoke-RestMethod -Uri "http://localhost:8080/api/militaires" -WebSession $mySessionRmia
    Write-Host "Militaires Count for RMIA1:" $mils.Count
} catch {
    Write-Host "Error fetching militaires for RMIA1:" $_.Exception.Message
}
