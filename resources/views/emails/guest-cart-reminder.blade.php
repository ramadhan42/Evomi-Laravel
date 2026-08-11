<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Salinan Keranjang Evomi</title>
</head>
<body style="margin:0;padding:0;background:#F3F6FA;font-family:Arial,Helvetica,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:24px 12px;background:#F3F6FA;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background:#fff;border-radius:16px;overflow:hidden;">
          <tr>
            <td style="background:{{ $brandColor }};padding:24px;text-align:center;">
              <p style="margin:0;color:rgba(255,255,255,.85);font-size:12px;letter-spacing:.16em;text-transform:uppercase;font-weight:700;">Peringatan Guest</p>
              <h1 style="margin:8px 0 0;color:#fff;font-size:24px;">Salinan Keranjang Evomi</h1>
            </td>
          </tr>
          <tr>
            <td style="padding:24px;">
              <p style="margin:0 0 12px;color:#334155;font-size:14px;line-height:1.6;">
                Halo! Keranjang guest di perangkat bisa hilang jika cache/browser dibersihkan. Berikut salinan item Anda:
              </p>
              <div style="margin:0 0 18px;padding:12px 14px;border-radius:12px;background:#FFF7ED;border:1px solid #FED7AA;color:#9A3412;font-size:13px;line-height:1.5;">
                Segera daftar &amp; login agar keranjang, pembayaran, dan pelacakan tidak hilang.
              </div>
              @foreach ($items as $item)
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px;border:1px solid #E2E8F0;border-radius:12px;">
                  <tr>
                    <td style="padding:12px 14px;">
                      <p style="margin:0;font-weight:700;color:#0F172A;font-size:14px;">{{ $item['title'] }}</p>
                      <p style="margin:4px 0 0;color:#64748B;font-size:12px;">Qty {{ $item['quantity'] }} · Rp {{ number_format((float) $item['line_total'], 0, ',', '.') }}</p>
                    </td>
                  </tr>
                </table>
              @endforeach
              <p style="margin:16px 0 0;font-size:15px;font-weight:700;color:#0F172A;">
                Total estimasi: Rp {{ number_format((float) $total, 0, ',', '.') }}
              </p>
              <table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:20px;">
                <tr>
                  <td style="padding-right:8px;">
                    <a href="{{ $registerUrl }}" style="display:inline-block;background:{{ $brandColor }};color:#fff;text-decoration:none;padding:10px 16px;border-radius:10px;font-size:13px;font-weight:700;">Daftar</a>
                  </td>
                  <td style="padding-right:8px;">
                    <a href="{{ $loginUrl }}" style="display:inline-block;background:#0F172A;color:#fff;text-decoration:none;padding:10px 16px;border-radius:10px;font-size:13px;font-weight:700;">Login</a>
                  </td>
                  <td>
                    <a href="{{ $checkoutUrl }}" style="display:inline-block;background:#F1F5F9;color:#1172BA;text-decoration:none;padding:10px 16px;border-radius:10px;font-size:13px;font-weight:700;">Lanjut Checkout</a>
                  </td>
                </tr>
              </table>
              <p style="margin:18px 0 0;color:#94A3B8;font-size:11px;">Dikirim ke {{ $guestEmail }}</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
