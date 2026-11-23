import os
import qrcode
from PIL import Image, ImageChops, ImageDraw
from qrcode.constants import ERROR_CORRECT_H

BASE = os.path.dirname(os.path.abspath(__file__))
urls = {
    "exit": "https://exit.com.pe/pages/lista-de-deseos",
    "mch": "https://mch.com.pe/pages/lista-de-deseos",
    "milkblues": "https://milkblues.com/pages/lista-de-deseos",
}

def recortar(img):
    img = img.convert("RGB")
    bbox = ImageChops.difference(img, Image.new("RGB", img.size, "white")).getbbox()
    return img.crop(bbox)

for n, u in urls.items():
    qr = qrcode.QRCode(error_correction=ERROR_CORRECT_H, box_size=20, border=4)
    qr.add_data(u); qr.make(fit=True)
    img = qr.make_image(fill_color="black", back_color="white").convert("RGB")
    path = os.path.join(BASE, "logos", f"{n}.png")
    if os.path.exists(path):
        logo = recortar(Image.open(path))
        W = img.size[0]
        lw = int(W * 0.30)                      # ancho del logo (30% del QR)
        lh = int(logo.size[1] * lw / logo.size[0])
        logo = logo.resize((lw, lh), Image.LANCZOS)
        pad = int(W * 0.025)
        box = Image.new("RGB", (lw + 2*pad, lh + 2*pad), "white")
        mask = Image.new("L", box.size, 0)
        ImageDraw.Draw(mask).rounded_rectangle([0, 0, *box.size], radius=pad*2, fill=255)
        box.paste(logo, (pad, pad))
        img.paste(box, ((W - box.size[0])//2, (W - box.size[1])//2), mask)
    img.save(os.path.join(BASE, f"qr_{n}_lista_deseos.png"))
    print(n, "con logo" if os.path.exists(path) else "sin logo")
