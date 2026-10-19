<div class="header">
    @if($company['logo_path'])
        <img src="{{ $company['logo_path'] }}" class="logo" alt="{{ $company['name'] ?? 'Company' }} logo">
    @endif
</div>
<div class="footer">{{ $company['footer'] }}</div>
