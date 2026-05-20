$repoPath = Split-Path -Parent $PSScriptRoot
$global:lastCommit = [DateTime]::MinValue
$debounceSeconds = 3

$watcher = New-Object System.IO.FileSystemWatcher $repoPath
$watcher.IncludeSubdirectories = $true
$watcher.EnableRaisingEvents = $true
$watcher.NotifyFilter = [System.IO.NotifyFilters]::LastWrite -bor [System.IO.NotifyFilters]::FileName

$action = {
    $path = $Event.SourceEventArgs.FullPath
    # Ignore .git internals
    if ($path -like "*\.git\*") { return }

    $now = [DateTime]::Now
    if (($now - $global:lastCommit).TotalSeconds -lt $debounceSeconds) { return }
    $global:lastCommit = $now

    Push-Location $repoPath
    git add -A
    $status = git status --porcelain
    if ($status) {
        $msg = "Auto-save: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
        git commit -m $msg
        git push
        Write-Host "[Auto-push] Committed and pushed at $(Get-Date -Format 'HH:mm:ss')"
    }
    Pop-Location
}

Register-ObjectEvent $watcher "Changed" -Action $action -SourceIdentifier "AutoPush_Changed"
Register-ObjectEvent $watcher "Created" -Action $action -SourceIdentifier "AutoPush_Created"
Register-ObjectEvent $watcher "Deleted" -Action $action -SourceIdentifier "AutoPush_Deleted"
Register-ObjectEvent $watcher "Renamed" -Action $action -SourceIdentifier "AutoPush_Renamed"

Write-Host "[Auto-push] Watching '$repoPath' for changes..."

try {
    while ($true) { Start-Sleep -Milliseconds 500 }
} finally {
    Unregister-Event -SourceIdentifier "AutoPush_Changed" -ErrorAction SilentlyContinue
    Unregister-Event -SourceIdentifier "AutoPush_Created" -ErrorAction SilentlyContinue
    Unregister-Event -SourceIdentifier "AutoPush_Deleted" -ErrorAction SilentlyContinue
    Unregister-Event -SourceIdentifier "AutoPush_Renamed" -ErrorAction SilentlyContinue
    $watcher.Dispose()
}
