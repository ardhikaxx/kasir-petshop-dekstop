using System;
using System.Drawing;
using System.Drawing.Drawing2D;
using System.Drawing.Imaging;
using System.IO;

class IconGenerator
{
    static void Main(string[] args)
    {
        string outputPath = args.Length > 0 ? args[0] : "build\\app.ico";
        int[] sizes = new int[] { 16, 24, 32, 48, 64, 128, 256 };

        byte[][] pngData = new byte[sizes.Length][];
        for (int i = 0; i < sizes.Length; i++)
        {
            using (Bitmap bmp = DrawPetIcon(sizes[i]))
            using (MemoryStream ms = new MemoryStream())
            {
                bmp.Save(ms, ImageFormat.Png);
                pngData[i] = ms.ToArray();
            }
        }

        using (FileStream fs = new FileStream(outputPath, FileMode.Create))
        using (BinaryWriter bw = new BinaryWriter(fs))
        {
            // ICONDIR header
            bw.Write((short)0); // reserved
            bw.Write((short)1); // type 1 = icon
            bw.Write((short)sizes.Length); // count

            int offset = 6 + (16 * sizes.Length);
            for (int i = 0; i < sizes.Length; i++)
            {
                int sz = sizes[i];
                bw.Write((byte)(sz >= 256 ? 0 : sz)); // width
                bw.Write((byte)(sz >= 256 ? 0 : sz)); // height
                bw.Write((byte)0); // color count
                bw.Write((byte)0); // reserved
                bw.Write((short)1); // planes
                bw.Write((short)32); // bit count
                bw.Write(pngData[i].Length); // size of image data
                bw.Write(offset); // offset

                offset += pngData[i].Length;
            }

            // Write image data
            for (int i = 0; i < sizes.Length; i++)
            {
                bw.Write(pngData[i]);
            }
        }

        Console.WriteLine("Icon created successfully at: " + Path.GetFullPath(outputPath));
    }

    static Bitmap DrawPetIcon(int size)
    {
        Bitmap bmp = new Bitmap(size, size, PixelFormat.Format32bppArgb);
        using (Graphics g = Graphics.FromImage(bmp))
        {
            g.SmoothingMode = SmoothingMode.AntiAlias;
            g.InterpolationMode = InterpolationMode.HighQualityBicubic;
            g.PixelOffsetMode = PixelOffsetMode.HighQuality;

            // Background Circle with Gradient
            float pad = size * 0.04f;
            RectangleF rect = new RectangleF(pad, pad, size - (pad * 2), size - (pad * 2));
            using (LinearGradientBrush bgBrush = new LinearGradientBrush(
                rect,
                Color.FromArgb(13, 148, 136), // Emerald / Teal
                Color.FromArgb(15, 118, 110), // Dark Teal
                LinearGradientMode.ForwardDiagonal))
            {
                g.FillEllipse(bgBrush, rect);
            }

            // Outer highlight ring
            using (Pen ringPen = new Pen(Color.FromArgb(204, 251, 241), Math.Max(1f, size * 0.03f)))
            {
                g.DrawEllipse(ringPen, rect);
            }

            // Draw White Paw Print
            float cx = size * 0.5f;
            float cy = size * 0.52f;
            using (Brush whiteBrush = new SolidBrush(Color.White))
            {
                // Main palm pad
                float palmW = size * 0.44f;
                float palmH = size * 0.36f;
                g.FillEllipse(whiteBrush, cx - (palmW / 2), cy - (palmH * 0.2f), palmW, palmH);

                // 4 Toes
                float toeW = size * 0.14f;
                float toeH = size * 0.19f;

                // Toe 1 (Far Left)
                g.FillEllipse(whiteBrush, cx - (size * 0.26f), cy - (size * 0.26f), toeW, toeH);

                // Toe 2 (Center Left)
                g.FillEllipse(whiteBrush, cx - (size * 0.10f), cy - (size * 0.34f), toeW, toeH);

                // Toe 3 (Center Right)
                g.FillEllipse(whiteBrush, cx + (size * 0.10f) - toeW, cy - (size * 0.34f), toeW, toeH);

                // Toe 4 (Far Right)
                g.FillEllipse(whiteBrush, cx + (size * 0.26f) - toeW, cy - (size * 0.26f), toeW, toeH);
            }
        }
        return bmp;
    }
}
