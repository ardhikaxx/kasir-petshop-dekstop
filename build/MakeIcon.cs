using System;
using System.Collections.Generic;
using System.Drawing;
using System.Drawing.Drawing2D;
using System.Drawing.Imaging;
using System.Globalization;
using System.IO;
using System.Text.RegularExpressions;

class IconGenerator
{
    static void Main(string[] args)
    {
        string svgPath = @"C:\Users\LENOVO\Downloads\logo-klinik2.svg";
        if (!File.Exists(svgPath))
        {
            svgPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, @"..\public\images\logo.svg");
            if (!File.Exists(svgPath))
            {
                svgPath = @"public\images\logo.svg";
            }
        }

        string outputIco = "build\\app.ico";
        string outputPng = "build\\logo-preview.png";

        for (int a = 0; a < args.Length; a++)
        {
            if (args[a].EndsWith(".svg", StringComparison.OrdinalIgnoreCase) && File.Exists(args[a]))
            {
                svgPath = args[a];
            }
            else if (args[a].EndsWith(".ico", StringComparison.OrdinalIgnoreCase))
            {
                outputIco = args[a];
            }
        }

        List<GraphicsPath> paths = ParseSvgPaths(svgPath);
        Console.WriteLine("Parsed " + paths.Count + " SVG vector paths from " + svgPath);

        // Calculate combined bounding box of all paths to perfectly center and maximize the graphic
        RectangleF bounds = RectangleF.Empty;
        foreach (GraphicsPath p in paths)
        {
            RectangleF b = p.GetBounds();
            if (bounds.IsEmpty)
            {
                bounds = b;
            }
            else
            {
                bounds = RectangleF.Union(bounds, b);
            }
        }
        Console.WriteLine(string.Format("Vector artwork bounds: X={0:F1}, Y={1:F1}, W={2:F1}, H={3:F1}", bounds.X, bounds.Y, bounds.Width, bounds.Height));

        int[] sizes = new int[] { 16, 24, 32, 48, 64, 128, 256 };
        byte[][] entryData = new byte[sizes.Length][];

        for (int i = 0; i < sizes.Length; i++)
        {
            int sz = sizes[i];
            using (Bitmap bmp = RenderIcon(paths, bounds, sz))
            {
                if (sz <= 128)
                {
                    // Sizes <= 128 MUST be standard Win32 32-bit DIB bitmaps
                    // Otherwise Windows Explorer fails to extract icons for Large/Medium/Small views
                    entryData[i] = CreateDibIconData(bmp);
                }
                else
                {
                    // 256x256 uses standard PNG compression in modern Windows ICO
                    entryData[i] = CreatePngIconData(bmp);
                }
            }
        }

        // Also save high-resolution preview PNG
        using (Bitmap previewBmp = RenderIcon(paths, bounds, 512))
        {
            previewBmp.Save(outputPng, ImageFormat.Png);
            Console.WriteLine("Preview PNG saved: " + Path.GetFullPath(outputPng));
        }

        // Save multi-resolution .ico with 100% Win32 PE specification compliance
        using (FileStream fs = new FileStream(outputIco, FileMode.Create))
        using (BinaryWriter bw = new BinaryWriter(fs))
        {
            bw.Write((short)0); // reserved
            bw.Write((short)1); // type 1 = icon
            bw.Write((short)sizes.Length); // count

            int offset = 6 + (16 * sizes.Length);
            for (int i = 0; i < sizes.Length; i++)
            {
                int sz = sizes[i];
                bw.Write((byte)(sz >= 256 ? 0 : sz)); // bWidth
                bw.Write((byte)(sz >= 256 ? 0 : sz)); // bHeight
                bw.Write((byte)0);                    // bColorCount (0 for >= 8bpp)
                bw.Write((byte)0);                    // bReserved
                bw.Write((short)1);                   // wPlanes
                bw.Write((short)32);                  // wBitCount (32-bit BGRA with alpha)
                bw.Write((int)entryData[i].Length);   // dwBytesInRes
                bw.Write((int)offset);                // dwImageOffset

                offset += entryData[i].Length;
            }

            for (int i = 0; i < sizes.Length; i++)
            {
                bw.Write(entryData[i]);
            }
        }

        Console.WriteLine("Application icon generated successfully at: " + Path.GetFullPath(outputIco));
    }

    static Bitmap RenderIcon(List<GraphicsPath> originalPaths, RectangleF bounds, int size)
    {
        Bitmap bmp = new Bitmap(size, size, PixelFormat.Format32bppArgb);
        using (Graphics g = Graphics.FromImage(bmp))
        {
            g.SmoothingMode = SmoothingMode.AntiAlias;
            g.InterpolationMode = InterpolationMode.HighQualityBicubic;
            g.PixelOffsetMode = PixelOffsetMode.HighQuality;
            g.Clear(Color.Transparent);

            // Background with Klinik Hewan primary color #D88C9A
            Color bgRose = Color.FromArgb(216, 140, 154); // #D88C9A
            Color borderDark = Color.FromArgb(183, 110, 121); // #B76E79

            float pad = size * 0.04f;
            float cornerRadius = size * 0.22f; // Soft rounded squircle
            RectangleF rect = new RectangleF(pad, pad, size - (pad * 2), size - (pad * 2));

            using (GraphicsPath bgPath = CreateRoundedRectanglePath(rect, cornerRadius))
            {
                using (SolidBrush brush = new SolidBrush(bgRose))
                {
                    g.FillPath(brush, bgPath);
                }
                using (Pen borderPen = new Pen(borderDark, Math.Max(1f, size * 0.025f)))
                {
                    g.DrawPath(borderPen, bgPath);
                }
            }

            // Draw white SVG paths scaled & centered perfectly inside squircle
            if (!bounds.IsEmpty && bounds.Width > 0 && bounds.Height > 0)
            {
                // Target width & height for artwork inside the icon
                float targetW = size * 0.76f;
                float targetH = size * 0.65f;

                float scale = Math.Min(targetW / bounds.Width, targetH / bounds.Height);
                float drawnW = bounds.Width * scale;
                float drawnH = bounds.Height * scale;

                float offsetX = (size - drawnW) / 2f - (bounds.X * scale);
                float offsetY = (size - drawnH) / 2f - (bounds.Y * scale);

                using (Brush whiteBrush = new SolidBrush(Color.White))
                {
                    foreach (GraphicsPath p in originalPaths)
                    {
                        using (GraphicsPath scaled = (GraphicsPath)p.Clone())
                        using (Matrix matrix = new Matrix())
                        {
                            matrix.Translate(offsetX, offsetY);
                            matrix.Scale(scale, scale);
                            scaled.Transform(matrix);
                            g.FillPath(whiteBrush, scaled);
                        }
                    }
                }
            }
        }
        return bmp;
    }

    static byte[] CreateDibIconData(Bitmap bmp)
    {
        int width = bmp.Width;
        int height = bmp.Height;
        int xorStride = width * 4;
        int andStride = ((width + 31) / 32) * 4;
        int xorSize = xorStride * height;
        int andSize = andStride * height;
        int headerSize = 40;
        int totalSize = headerSize + xorSize + andSize;

        byte[] data = new byte[totalSize];
        using (MemoryStream ms = new MemoryStream(data))
        using (BinaryWriter bw = new BinaryWriter(ms))
        {
            // BITMAPINFOHEADER
            bw.Write((uint)headerSize);          // biSize
            bw.Write((int)width);                // biWidth
            bw.Write((int)(height * 2));         // biHeight (XOR height + AND height)
            bw.Write((ushort)1);                 // biPlanes
            bw.Write((ushort)32);                // biBitCount (32-bit BGRA)
            bw.Write((uint)0);                   // biCompression (BI_RGB)
            bw.Write((uint)(xorSize + andSize)); // biSizeImage
            bw.Write((int)0);                    // biXPelsPerMeter
            bw.Write((int)0);                    // biYPelsPerMeter
            bw.Write((uint)0);                   // biClrUsed
            bw.Write((uint)0);                   // biClrImportant

            // XOR mask: 32-bit BGRA, stored bottom-up scanlines
            for (int y = height - 1; y >= 0; y--)
            {
                for (int x = 0; x < width; x++)
                {
                    Color c = bmp.GetPixel(x, y);
                    bw.Write(c.B);
                    bw.Write(c.G);
                    bw.Write(c.R);
                    bw.Write(c.A);
                }
            }

            // AND mask: 1-bit per pixel, stored bottom-up scanlines, padded to 32 bits
            for (int y = height - 1; y >= 0; y--)
            {
                byte currentByte = 0;
                int bitCount = 0;
                int writtenBytes = 0;

                for (int x = 0; x < width; x++)
                {
                    Color c = bmp.GetPixel(x, y);
                    // 1 bit: 1 = transparent, 0 = opaque
                    if (c.A == 0)
                    {
                        currentByte |= (byte)(0x80 >> bitCount);
                    }

                    bitCount++;
                    if (bitCount == 8)
                    {
                        bw.Write(currentByte);
                        writtenBytes++;
                        currentByte = 0;
                        bitCount = 0;
                    }
                }

                if (bitCount > 0)
                {
                    bw.Write(currentByte);
                    writtenBytes++;
                }

                while (writtenBytes % 4 != 0)
                {
                    bw.Write((byte)0);
                    writtenBytes++;
                }
            }
        }

        return data;
    }

    static byte[] CreatePngIconData(Bitmap bmp)
    {
        using (MemoryStream ms = new MemoryStream())
        {
            bmp.Save(ms, ImageFormat.Png);
            return ms.ToArray();
        }
    }

    static GraphicsPath CreateRoundedRectanglePath(RectangleF rect, float radius)
    {
        GraphicsPath path = new GraphicsPath();
        float d = radius * 2;
        path.AddArc(rect.X, rect.Y, d, d, 180, 90);
        path.AddArc(rect.Right - d, rect.Y, d, d, 270, 90);
        path.AddArc(rect.Right - d, rect.Bottom - d, d, d, 0, 90);
        path.AddArc(rect.X, rect.Bottom - d, d, d, 90, 90);
        path.CloseFigure();
        return path;
    }

    static List<GraphicsPath> ParseSvgPaths(string svgFile)
    {
        List<GraphicsPath> result = new List<GraphicsPath>();
        string content = File.ReadAllText(svgFile);

        MatchCollection matches = Regex.Matches(content, @"<path[^>]*\bd=[""']([^""']+)[""']");
        foreach (Match m in matches)
        {
            string d = m.Groups[1].Value;
            GraphicsPath gp = ParseSvgPathData(d);
            if (gp != null && gp.PointCount > 0)
            {
                result.Add(gp);
            }
        }

        return result;
    }

    static GraphicsPath ParseSvgPathData(string d)
    {
        GraphicsPath path = new GraphicsPath();
        path.FillMode = FillMode.Alternate; // SVG evenodd rule

        MatchCollection tokens = Regex.Matches(d, @"([a-zA-Z])|([-+]?[0-9]*\.?[0-9]+(?:[eE][-+]?[0-9]+)?)");

        int index = 0;
        PointF currentPoint = new PointF(0, 0);
        PointF startPoint = new PointF(0, 0);
        char currentCommand = ' ';

        while (index < tokens.Count)
        {
            string val = tokens[index].Value;
            if (char.IsLetter(val[0]))
            {
                currentCommand = val[0];
                index++;
            }

            switch (currentCommand)
            {
                case 'M':
                    if (index + 1 < tokens.Count)
                    {
                        float x = float.Parse(tokens[index++].Value, CultureInfo.InvariantCulture);
                        float y = float.Parse(tokens[index++].Value, CultureInfo.InvariantCulture);
                        currentPoint = new PointF(x, y);
                        startPoint = currentPoint;
                        path.StartFigure();
                        currentCommand = 'L'; // Subsequent pairs are lines
                    }
                    break;

                case 'L':
                    if (index + 1 < tokens.Count)
                    {
                        float x = float.Parse(tokens[index++].Value, CultureInfo.InvariantCulture);
                        float y = float.Parse(tokens[index++].Value, CultureInfo.InvariantCulture);
                        PointF target = new PointF(x, y);
                        path.AddLine(currentPoint, target);
                        currentPoint = target;
                    }
                    break;

                case 'H':
                    if (index < tokens.Count)
                    {
                        float x = float.Parse(tokens[index++].Value, CultureInfo.InvariantCulture);
                        PointF target = new PointF(x, currentPoint.Y);
                        path.AddLine(currentPoint, target);
                        currentPoint = target;
                    }
                    break;

                case 'V':
                    if (index < tokens.Count)
                    {
                        float y = float.Parse(tokens[index++].Value, CultureInfo.InvariantCulture);
                        PointF target = new PointF(currentPoint.X, y);
                        path.AddLine(currentPoint, target);
                        currentPoint = target;
                    }
                    break;

                case 'C':
                    if (index + 5 < tokens.Count)
                    {
                        float x1 = float.Parse(tokens[index++].Value, CultureInfo.InvariantCulture);
                        float y1 = float.Parse(tokens[index++].Value, CultureInfo.InvariantCulture);
                        float x2 = float.Parse(tokens[index++].Value, CultureInfo.InvariantCulture);
                        float y2 = float.Parse(tokens[index++].Value, CultureInfo.InvariantCulture);
                        float x = float.Parse(tokens[index++].Value, CultureInfo.InvariantCulture);
                        float y = float.Parse(tokens[index++].Value, CultureInfo.InvariantCulture);
                        PointF target = new PointF(x, y);
                        path.AddBezier(currentPoint, new PointF(x1, y1), new PointF(x2, y2), target);
                        currentPoint = target;
                    }
                    break;

                case 'Z':
                case 'z':
                    path.CloseFigure();
                    currentPoint = startPoint;
                    break;

                default:
                    index++;
                    break;
            }
        }

        return path;
    }
}
