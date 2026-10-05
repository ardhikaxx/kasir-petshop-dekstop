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

        int[] sizes = new int[] { 16, 24, 32, 48, 64, 128, 256 };
        byte[][] pngData = new byte[sizes.Length][];

        for (int i = 0; i < sizes.Length; i++)
        {
            using (Bitmap bmp = RenderIcon(paths, sizes[i]))
            using (MemoryStream ms = new MemoryStream())
            {
                bmp.Save(ms, ImageFormat.Png);
                pngData[i] = ms.ToArray();
            }
        }

        // Also save preview PNG
        using (Bitmap previewBmp = RenderIcon(paths, 512))
        {
            previewBmp.Save(outputPng, ImageFormat.Png);
            Console.WriteLine("Preview PNG saved: " + Path.GetFullPath(outputPng));
        }

        // Save multi-resolution .ico
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
                bw.Write((byte)(sz >= 256 ? 0 : sz));
                bw.Write((byte)(sz >= 256 ? 0 : sz));
                bw.Write((byte)0);
                bw.Write((byte)0);
                bw.Write((short)1);
                bw.Write((short)32);
                bw.Write(pngData[i].Length);
                bw.Write(offset);

                offset += pngData[i].Length;
            }

            for (int i = 0; i < sizes.Length; i++)
            {
                bw.Write(pngData[i]);
            }
        }

        Console.WriteLine("Application icon generated successfully at: " + Path.GetFullPath(outputIco));
    }

    static Bitmap RenderIcon(List<GraphicsPath> originalPaths, int size)
    {
        Bitmap bmp = new Bitmap(size, size, PixelFormat.Format32bppArgb);
        using (Graphics g = Graphics.FromImage(bmp))
        {
            g.SmoothingMode = SmoothingMode.AntiAlias;
            g.InterpolationMode = InterpolationMode.HighQualityBicubic;
            g.PixelOffsetMode = PixelOffsetMode.HighQuality;

            // Background with theme primary color #D88C9A
            Color bgRose = Color.FromArgb(216, 140, 154); // #D88C9A
            Color borderDark = Color.FromArgb(183, 110, 121); // #B76E79

            float pad = size * 0.03f;
            float cornerRadius = size * 0.22f; // Soft rounded squircle
            RectangleF rect = new RectangleF(pad, pad, size - (pad * 2), size - (pad * 2));

            using (GraphicsPath bgPath = CreateRoundedRectanglePath(rect, cornerRadius))
            {
                using (SolidBrush brush = new SolidBrush(bgRose))
                {
                    g.FillPath(brush, bgPath);
                }
                using (Pen borderPen = new Pen(borderDark, Math.Max(1f, size * 0.02f)))
                {
                    g.DrawPath(borderPen, bgPath);
                }
            }

            // Draw white SVG paths scaled to fit nicely inside
            // SVG viewBox is 5371 x 5371
            float scale = (size * 0.88f) / 5371f;
            float offsetX = size * 0.06f;
            float offsetY = size * 0.06f;

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
        return bmp;
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
