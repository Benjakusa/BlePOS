<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; background-color: #F6F8FB; color: #0F172A; padding: 24px; margin: 0; }
        .box { max-width: 520px; margin: 0 auto; background: #FFFFFF; border-radius: 14px; padding: 32px; box-shadow: 0 2px 8px rgba(15,23,42,0.06); }
        .btn { display: inline-block; background-color: #16A34A; color: #FFFFFF !important; text-decoration: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; margin: 20px 0; }
        .muted { color: #64748B; font-size: 13px; }
    </style>
</head>
<body>
    <div class="box">
        <h2 style="margin-top: 0; font-size: 20px; color: #0F172A;">BlePOS Platform Invitation</h2>
        <p>You have been invited to join the BlePOS platform administration team.</p>
        <p style="text-align: center;">
            <a href="{{ $inviteUrl }}" class="btn">Accept Invitation</a>
        </p>
        <p class="muted">This invitation link expires at {{ $expiresAt->toDayDateTimeString() }}.</p>
        <p class="muted" style="margin-bottom: 0;">If you did not expect this invitation, you can safely ignore this message.</p>
    </div>
</body>
</html>
