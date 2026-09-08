# SEO Toolkit - Advanced Website Analysis Tool

A comprehensive, professional-grade SEO analysis tool for web developers. Extract meta tags, analyze SEO health, generate actionable recommendations, and optimize your website for search engines with detailed insights.

![SEO Score](https://img.shields.io/badge/Analysis%20Score-0--100-blue)
![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-777BB4)
![License](https://img.shields.io/badge/License-MIT-green)

## 🎯 Features

### Core Analysis Capabilities
- **Comprehensive Meta Tag Extraction** - Captures all meta tags, Open Graph, Twitter Cards, and structured data
- **SEO Health Scoring** - 0-100 point scoring system with detailed breakdown across 10 categories
- **Intelligent Recommendations** - Priority-based actionable suggestions to improve SEO rankings
- **Flexible URL Input** - Accepts domain-only input (e.g., `google.com`) or full URLs with automatic protocol handling
- **Heading Structure Analysis** - Tracks H1-H6 tags with content extraction
- **Mobile-Friendly Detection** - Validates viewport meta tags and responsive design markers

### Content Analysis
- **Word Count Analysis** - Measures page content depth
- **Image Optimization Audit** - Counts images with alt text and title attributes
- **Link Analysis** - Distinguishes internal/external links and tracks nofollow attributes
- **Structured Data Detection** - Identifies JSON-LD schema and microdata implementations

### Technical SEO Checks
- Charset and language attributes
- Canonical URL presence
- Robots meta tag verification
- Meta refresh detection (bad practice warning)
- Favicon detection
- Mobile viewport validation
- Social media optimization scoring

### User Experience
- 🌙 **Dark Mode** - Toggle with system preference detection and localStorage persistence
- 📊 **5-Tab Interface** - Organized views: Overview, Meta Tags, Content, Technical, Raw Data
- 📋 **Export Functionality** - Download complete analysis as JSON with timestamp
- 📋 **Copy to Clipboard** - Quick copy URL or raw analysis data
- 📱 **Responsive Design** - Seamless experience on desktop, tablet, and mobile
- 🎨 **Professional UI** - Modern gradient design with smooth animations and transitions

## 📋 Quick Start

### Prerequisites
- PHP 7.4 or higher
- A web server (Apache, Nginx, or PHP built-in server)
- Modern web browser (Chrome, Firefox, Safari, Edge)

### Installation

#### Option 1: Direct Server Upload
1. Clone or download the repository:
   ```bash
   git clone https://github.com/yourusername/seo-meta-tag-extractor.git
   cd seo-meta-tag-extractor
   ```

2. Upload files to your web server:
   - `index.php`
   - `style.css`
   - `script.js`

3. Access via browser:
   ```
   https://yourdomain.com/seo-meta-tag-extractor/
   ```

#### Option 2: Local Development with PHP Built-in Server
```bash
cd seo-meta-tag-extractor
php -S localhost:8000
```

Then open `http://localhost:8000` in your browser.

## 🚀 Usage

### Basic Analysis
1. Enter a website URL in the search box
2. Click "Analyze" button
3. View comprehensive SEO report with recommendations

### URL Input Flexibility
- **Domain only**: `google.com` → Automatically converts to `https://google.com`
- **With protocol**: `https://example.com` → Analyzed as-is
- **Localhost**: `localhost:8000` → Supported for local testing

### Interpreting Results

#### SEO Health Score
- **80-100**: ✓ Excellent SEO - Ready for search engine optimization
- **60-79**: ✓ Good SEO - Some improvements recommended
- **40-59**: ⚠ Average SEO - Multiple issues need attention
- **0-39**: ✗ Needs Improvement - Critical issues present

#### Recommendations Priority Levels
- 🔴 **Critical** - Essential for SEO and indexing
- 🟠 **High** - Strongly impacts search rankings
- 🔵 **Medium** - Improves user experience and metadata
- 🟢 **Low** - Nice-to-have enhancements

### Tab Guide

| Tab | Content |
|-----|---------|
| **Overview** | Summary metrics, heading structure, link counts, canonical URL |
| **Meta Tags** | Open Graph tags, Twitter Card meta, language, charset |
| **Content** | Word count, image metrics with alt text percentage, H1 contents |
| **Technical** | Technical checklist, structured data, favicon, mobile compatibility |
| **Raw Data** | Complete JSON export of all analysis data |

## 📊 Analysis Metrics Explained

### Scoring Categories (100 Points Total)
| Category | Points | Focus |
|----------|--------|-------|
| Title Tag | 15 | Presence and optimal length (30-60 chars) |
| Meta Description | 15 | Presence and optimal length (120-160 chars) |
| H1 Tags | 10 | Single H1 per page for semantic structure |
| Open Graph Tags | 15 | Social media sharing optimization |
| Twitter Card | 5 | Twitter-specific sharing metadata |
| Canonical URL | 10 | Duplicate content prevention |
| Viewport Meta | 10 | Mobile responsiveness |
| Structured Data | 10 | Schema.org JSON-LD implementation |
| Image Alt Text | 5 | Accessibility and image SEO |
| Robots Meta | 5 | Crawling and indexing control |
| **Bonus** | +6 | Charset, language, favicon |

## 🎨 Features Showcase

### Dark Mode
Automatically detects system preference and allows manual toggle. Theme persists across sessions using localStorage.

### Circular Progress Indicator
Animated SVG visualization of SEO score with color gradient from blue to orange based on score percentage.

### Priority-Coded Recommendations
Color-coded recommendation items with priority icons:
- Critical issues in red
- High priority in orange
- Medium priority in blue
- Low priority in green

### JSON Export
Complete analysis export includes:
- All extracted meta tags
- Scoring breakdown
- Recommendations with priority levels
- Technical validation results
- Timestamp of analysis

## 📁 Project Structure

```
seo-meta-tag-extractor/
├── index.php          # Main PHP application with backend analysis
├── style.css          # Complete responsive styling system
├── script.js          # Client-side interactivity
└── README.md          # Documentation (this file)
```

### File Responsibilities

**index.php** (560+ lines)
- `normalizeURL()` - URL validation and protocol handling
- `fetchMetaTags()` - Core analysis engine with HTTP context and 40+ data points
- `calculateSEOScore()` - Scoring algorithm with recommendations generation
- HTML template with responsive layout

**style.css** (600+ lines)
- CSS variable theming system
- Dark mode implementation
- Responsive grid layouts
- Smooth animations and transitions
- SVG animation for score indicator
- Print-friendly styling

**script.js** (150+ lines)
- Dark mode toggle with system preference detection
- Tab navigation system
- Form submission handling
- Clipboard operations
- Toast notifications
- JSON export functionality

## 🔧 Technical Details

### Backend Architecture
- **HTTP Context**: 15-second timeout with redirect following (max 5 redirects)
- **SSL Handling**: Compatibility mode for diverse hosting environments
- **DOM Parsing**: PHP DOMDocument for accurate HTML analysis
- **Error Handling**: User-friendly error messages with status codes
- **Data Output**: Structured array with 40+ analysis fields

### Frontend Architecture
- **Vanilla JavaScript**: No dependencies for lightweight deployment
- **Responsive Design**: CSS Grid and Flexbox for all screen sizes
- **Accessibility**: Semantic HTML, ARIA labels, keyboard navigation
- **Performance**: Minimal blocking operations, efficient DOM manipulation

### Security Considerations
- Input validation on all URL submissions
- HTML entity encoding for display output
- Stream context for controlled HTTP access
- No storage of sensitive user data

## 🌐 Browser Compatibility

| Browser | Support |
|---------|---------|
| Chrome | ✓ Full Support |
| Firefox | ✓ Full Support |
| Safari | ✓ Full Support |
| Edge | ✓ Full Support |
| IE 11 | ✗ Not Supported |

## 📈 Use Cases

### For Web Developers
- Quick SEO audit before deployment
- Client website analysis and reporting
- Competitive analysis for SEO planning
- On-page optimization verification

### For SEO Specialists
- Fast preliminary website assessment
- Recommendation prioritization
- Multi-site batch analysis
- Technical SEO verification

### For Content Managers
- Meta tag completeness checking
- Social media optimization verification
- Content length adequacy assessment
- Structural markup validation

## 🚨 Limitations & Known Issues

- Analysis requires direct website accessibility (some restrictive servers may block)
- Very large websites may timeout (15-second limit)
- JavaScript-rendered content not analyzed (static HTML only)
- Scoring based on technical factors (doesn't measure content quality)
- No historical tracking across multiple analyses

## 🔄 Version History

### v1.0 (Current)
- ✅ Core SEO analysis functionality
- ✅ Flexible URL input with automatic protocol handling
- ✅ Comprehensive recommendations system with priority levels
- ✅ Dark mode with system preference detection
- ✅ Multi-tab interface for organized data presentation
- ✅ JSON export functionality
- ✅ Responsive mobile design
- ✅ Professional UI with animations

## 📝 Contributing

Contributions are welcome! Please follow these guidelines:

1. **Fork** the repository
2. **Create** a feature branch: `git checkout -b feature/your-feature`
3. **Commit** changes: `git commit -m 'Add your feature'`
4. **Push** to branch: `git push origin feature/your-feature`
5. **Submit** a Pull Request with description of changes

### Areas for Contribution
- Additional SEO metrics and scoring categories
- Support for more structured data types (Microdata, RDFa)
- Historical analysis tracking
- API endpoint for programmatic access
- Additional language support
- Performance optimizations

## 📧 Support & Contact

For questions, issues, or suggestions:
- **Issues**: Submit via GitHub Issues page
- **Email**: [your-email@example.com]
- **Twitter**: [@yourhandle]

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

### MIT License Summary
- ✓ Commercial use
- ✓ Modification
- ✓ Distribution
- ✓ Private use
- ✗ Liability
- ✗ Warranty

## 🙏 Acknowledgments

- **Font Awesome 6.4.0** - Icon library
- **Google Fonts** - Poppins typeface
- **PHP DOMDocument** - HTML parsing
- Community feedback and suggestions

## 🎓 Learn More

- [SEO Best Practices](https://developers.google.com/search)
- [Schema.org Documentation](https://schema.org/)
- [Open Graph Protocol](https://ogp.me/)
- [Twitter Card Documentation](https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/abouts-cards)

---

**Made with ❤️ for web developers and SEO professionals**

⭐ If you find this tool helpful, please consider giving it a star on GitHub!
