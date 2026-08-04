<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Order Evomi</title>
  <!--[if mso]>
  <style type="text/css">
    body, table, td { font-family: Arial, Helvetica, sans-serif !important; }
  </style>
  <![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#F3F6FA;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;">
    Konfirmasi pesanan Evomi #{{ $orderId }} — terima kasih sudah belanja!
  </div>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F3F6FA;padding:24px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background-color:#ffffff;border-radius:16px;overflow:hidden;">

          {{-- ===== BLUE HEADER ===== --}}
          <tr>
            <td align="center" style="background-color:#0071BC;padding:28px 24px;">
              <p style="margin:0;font-size:13px;letter-spacing:0.18em;text-transform:uppercase;color:rgba(255,255,255,0.85);font-weight:700;">
                Konfirmasi Pesanan
              </p>
              <h1 style="margin:8px 0 0;font-size:30px;line-height:1.2;color:#FFFFFF;font-weight:800;">
                Order Evomi
              </h1>
              <p style="margin:10px 0 0;font-size:14px;color:rgba(255,255,255,0.92);">
                #{{ $orderId }}
              </p>
            </td>
          </tr>

          {{-- ===== BODY ===== --}}
          <tr>
            <td style="background-color:#FFFFFF;padding:24px 28px 8px;">
              <p style="margin:0 0 6px;font-size:18px;font-weight:700;color:#0F172A;">
                Terima kasih, pesananmu sudah kami terima!
              </p>
              <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#475569;">
                Tim Evomi sedang memproses pesananmu. Simpan email ini sebagai bukti pemesanan, atau lacak status pengiriman kapan saja.
              </p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F0F7FC;border-radius:12px;margin-bottom:22px;">
                <tr>
                  <td style="padding:16px 18px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td style="padding:4px 0;font-size:13px;color:#64748B;width:42%;">Nomor pesanan</td>
                        <td style="padding:4px 0;font-size:13px;color:#0F172A;font-weight:700;text-align:right;">{{ $orderId }}</td>
                      </tr>
                      <tr>
                        <td style="padding:4px 0;font-size:13px;color:#64748B;">Metode pembayaran</td>
                        <td style="padding:4px 0;font-size:13px;color:#0F172A;font-weight:700;text-align:right;">{{ $paymentMethod }}</td>
                      </tr>
                      <tr>
                        <td style="padding:4px 0;font-size:13px;color:#64748B;">Total</td>
                        <td style="padding:4px 0;font-size:16px;color:#0071BC;font-weight:800;text-align:right;">Rp {{ number_format($total, 0, ',', '.') }}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 12px;font-size:14px;font-weight:700;color:#0F172A;letter-spacing:0.04em;text-transform:uppercase;">
                Produk dipesan
              </p>

              @foreach ($items as $item)
                @php
                  $productImg = null;
                  if (!empty($item['image_path']) && is_file($item['image_path'])) {
                      $productImg = $message->embed($item['image_path']);
                  } elseif (!empty($item['image_url'])) {
                      $productImg = $item['image_url'];
                  }
                @endphp
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #E2E8F0;border-radius:14px;margin-bottom:14px;overflow:hidden;">
                  <tr>
                    <td width="112" valign="middle" style="width:112px;padding:14px;background-color:#F8FAFC;">
                      @if ($productImg)
                        <img src="{{ $productImg }}" alt="{{ $item['title'] }}" width="84" style="display:block;width:84px;height:84px;object-fit:cover;border-radius:10px;border:0;">
                      @else
                        <div style="width:84px;height:84px;border-radius:10px;background-color:#E2E8F0;text-align:center;line-height:84px;color:#94A3B8;font-size:12px;">Evomi</div>
                      @endif
                    </td>
                    <td valign="middle" style="padding:14px 16px 14px 0;">
                      <p style="margin:0 0 6px;font-size:15px;font-weight:700;color:#0F172A;">{{ $item['title'] }}</p>
                      <p style="margin:0 0 4px;font-size:13px;color:#64748B;">Qty: {{ $item['quantity'] }}</p>
                      <p style="margin:0;font-size:14px;font-weight:700;color:#0071BC;">
                        Rp {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}
                      </p>
                    </td>
                  </tr>
                </table>
              @endforeach

              <p style="margin:18px 0 12px;font-size:14px;font-weight:700;color:#0F172A;letter-spacing:0.04em;text-transform:uppercase;">
                Pengiriman
              </p>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:22px;">
                <tr>
                  <td style="padding:14px 16px;background-color:#FAFBFC;border-radius:12px;border:1px solid #EEF2F7;">
                    <p style="margin:0 0 6px;font-size:14px;color:#0F172A;"><strong>Penerima:</strong> {{ $recipient['name'] }}</p>
                    <p style="margin:0 0 6px;font-size:14px;color:#0F172A;"><strong>Telepon:</strong> {{ $recipient['phone'] }}</p>
                    <p style="margin:0 0 6px;font-size:14px;color:#0F172A;line-height:1.5;"><strong>Alamat:</strong> {{ $recipient['address'] }}</p>
                    @if (!empty($recipient['courier']))
                      <p style="margin:0;font-size:14px;color:#0F172A;"><strong>Kurir:</strong> {{ $recipient['courier'] }}</p>
                    @endif
                  </td>
                </tr>
              </table>

              <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto 10px;">
                <tr>
                  <td align="center" bgcolor="#0071BC" style="border-radius:999px;">
                    <a href="{{ $trackingUrl }}" style="display:inline-block;padding:14px 28px;font-size:14px;font-weight:700;color:#FFFFFF;text-decoration:none;border-radius:999px;background-color:#0071BC;">
                      Lacak Pesanan
                    </a>
                  </td>
                </tr>
              </table>
              <p style="margin:0 0 8px;text-align:center;font-size:12px;color:#94A3B8;word-break:break-all;">
                atau buka: <a href="{{ $trackingUrl }}" style="color:#0071BC;text-decoration:underline;">{{ $trackingUrl }}</a>
              </p>
            </td>
          </tr>

          {{-- ===== FOOTER ===== --}}
          <tr>
            <td style="background-color:#0B3A5C;padding:24px;">
              <p style="margin:0 0 6px;text-align:center;font-size:16px;font-weight:800;color:#FFFFFF;">
                Ikuti Evomi
              </p>
              <p style="margin:0 0 16px;text-align:center;font-size:13px;line-height:1.5;color:rgba(255,255,255,0.78);">
                Cerita aroma, drop terbaru, dan dunia karakter Evomi — di social media kami.
              </p>
              <table role="presentation" align="center" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 16px;">
                <tr>
                  <td style="padding:0 8px;">
                    <a href="{{ $social['instagram'] }}" style="font-size:13px;font-weight:700;color:#5CB2ED;text-decoration:none;">Instagram</a>
                  </td>
                  <td style="padding:0 8px;color:rgba(255,255,255,0.35);">|</td>
                  <td style="padding:0 8px;">
                    <a href="{{ $social['twitter'] }}" style="font-size:13px;font-weight:700;color:#5CB2ED;text-decoration:none;">Twitter / X</a>
                  </td>
                  <td style="padding:0 8px;color:rgba(255,255,255,0.35);">|</td>
                  <td style="padding:0 8px;">
                    <a href="{{ $social['facebook'] }}" style="font-size:13px;font-weight:700;color:#5CB2ED;text-decoration:none;">Facebook</a>
                  </td>
                </tr>
              </table>
              <p style="margin:0;text-align:center;font-size:11px;color:rgba(255,255,255,0.45);">
                © {{ date('Y') }} Evomi. Every Version of Me.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
