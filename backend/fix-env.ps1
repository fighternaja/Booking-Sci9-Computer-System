# Script to fix .env file - remove duplicate APP_KEY line
$envFile = ".env"
$content = Get-Content $envFile
$filtered = $content | Where-Object { $_ -notmatch "APP_KEY=<" }
$filtered | Set-Content $envFile
Write-Host "Fixed .env file!"

