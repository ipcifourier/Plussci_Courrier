param(
    [Parameter(Mandatory = $true)]
    [string]$RepoOwner,

    [Parameter(Mandatory = $true)]
    [string]$RepoName,

    [Parameter(Mandatory = $false)]
    [string]$GithubToken = $env:GITHUB_TOKEN,

    [Parameter(Mandatory = $false)]
    [string]$WorkflowFile = 'build-macos-sync-client.yml',

    [Parameter(Mandatory = $false)]
    [string]$ArtifactName = 'plussci-sync-client-macos',

    [Parameter(Mandatory = $false)]
    [string]$OutputPath = 'c:\xampp\htdocs\courrier-plussci\desktop-client\dist\plussci-sync-client-macos.tar.gz'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

if ([string]::IsNullOrWhiteSpace($GithubToken)) {
    throw 'GitHub token manquant. Passez -GithubToken ou definissez GITHUB_TOKEN.'
}

$headers = @{
    Authorization = "Bearer $GithubToken"
    Accept = 'application/vnd.github+json'
    'X-GitHub-Api-Version' = '2022-11-28'
    'User-Agent' = 'plussci-sync-deployer'
}

$baseApi = "https://api.github.com/repos/$RepoOwner/$RepoName"

Write-Host "[1/6] Recherche du dernier run reussi pour $WorkflowFile" -ForegroundColor Cyan
$runsUri = "$baseApi/actions/workflows/$WorkflowFile/runs?status=success&per_page=20"
$runsResponse = Invoke-RestMethod -Method Get -Uri $runsUri -Headers $headers

if (-not $runsResponse.workflow_runs -or $runsResponse.workflow_runs.Count -eq 0) {
    throw "Aucun run reussi trouve pour $WorkflowFile"
}

$run = $runsResponse.workflow_runs | Select-Object -First 1
Write-Host "Run selectionne: #$($run.run_number) ($($run.created_at))" -ForegroundColor Green

Write-Host "[2/6] Recuperation des artefacts du run" -ForegroundColor Cyan
$artifactsUri = "$baseApi/actions/runs/$($run.id)/artifacts"
$artifactsResponse = Invoke-RestMethod -Method Get -Uri $artifactsUri -Headers $headers

if (-not $artifactsResponse.artifacts -or $artifactsResponse.artifacts.Count -eq 0) {
    throw 'Aucun artefact disponible sur ce run.'
}

$artifact = $artifactsResponse.artifacts | Where-Object { $_.name -eq $ArtifactName } | Select-Object -First 1

if (-not $artifact) {
    $available = ($artifactsResponse.artifacts | ForEach-Object { $_.name }) -join ', '
    throw "Artefact '$ArtifactName' introuvable. Disponibles: $available"
}

if ($artifact.expired) {
    throw "L'artefact '$ArtifactName' est expire."
}

Write-Host "Artefact trouve: $($artifact.name)" -ForegroundColor Green

$tempRoot = Join-Path $env:TEMP ("plussci-macos-artifact-" + [guid]::NewGuid().ToString('N'))
$tempZip = Join-Path $tempRoot 'artifact.zip'
$tempExtract = Join-Path $tempRoot 'extract'
New-Item -ItemType Directory -Path $tempExtract -Force | Out-Null

try {
    Write-Host "[3/6] Telechargement de l'artefact" -ForegroundColor Cyan
    Invoke-WebRequest -Uri $artifact.archive_download_url -Headers $headers -OutFile $tempZip

    Write-Host "[4/6] Extraction de l'artefact" -ForegroundColor Cyan
    Expand-Archive -Path $tempZip -DestinationPath $tempExtract -Force

    $tarball = Get-ChildItem -Path $tempExtract -Recurse -File | Where-Object { $_.Name -eq 'plussci-sync-client-macos.tar.gz' } | Select-Object -First 1

    if (-not $tarball) {
        throw "Le fichier plussci-sync-client-macos.tar.gz est absent de l'artefact."
    }

    $outputDir = Split-Path -Parent $OutputPath
    if (-not (Test-Path $outputDir)) {
        New-Item -ItemType Directory -Path $outputDir -Force | Out-Null
    }

    Write-Host "[5/6] Publication de l'artefact dans $OutputPath" -ForegroundColor Cyan
    Copy-Item -Path $tarball.FullName -Destination $OutputPath -Force

    Write-Host "[6/6] Verification" -ForegroundColor Cyan
    $published = Get-Item -Path $OutputPath
    Write-Host ("Publie: " + $published.FullName) -ForegroundColor Green
    Write-Host ("Taille: " + $published.Length + " octets") -ForegroundColor Green
    Write-Host ("Date: " + $published.LastWriteTime.ToString('yyyy-MM-dd HH:mm:ss')) -ForegroundColor Green
}
finally {
    if (Test-Path $tempRoot) {
        Remove-Item -Path $tempRoot -Recurse -Force
    }
}
