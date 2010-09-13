<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    version="1.0">
<xsl:output
    indent="yes"
    method="html"
    omit-xml-declaration="no"
    encoding="UTF-8"
    doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
    doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />

<!-- @author Bernard Paques -->
<!-- @reference -->
<!-- @licence http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License -->

<xsl:template match="/">
	<html  xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><xsl:value-of select="data/page_title" disable-output-escaping="yes" /></title>
		<style type="text/css">
			dt {
				margin-top: 1em;
			}

			dd {
				margin-left: 2px ;
				padding-left: 0.5em;
				border-left: 1px solid #ccc;
			}
		</style>
	</head>
	<body id="tools" class="extra">

  	<xsl:apply-templates />

	</body>
	</html>

</xsl:template>

<!-- parse data -->
<xsl:template match="data">

	<!-- page title -->
	<h1><xsl:value-of select="page_title" disable-output-escaping="yes" /></h1>

	<!-- attributes made available to the skin template -->
	<dl>

	<dt>page_title</dt>
    	<dd><xsl:value-of select="page_title" disable-output-escaping="yes" /></dd>

    <dt>site_name *</dt>
    	<dd><xsl:value-of select="site_name" disable-output-escaping="yes" /></dd>

	<dt>site_copyright *</dt>
    	<dd><xsl:value-of select="site_copyright" disable-output-escaping="yes" /></dd>

	<dt>language</dt>
    	<dd><xsl:value-of select="language" disable-output-escaping="yes" /></dd>

	<dt>skin_variant</dt>
    	<dd><xsl:value-of select="skin_variant" disable-output-escaping="yes" /></dd>

	<dt>site_email *</dt>
    	<dd><xsl:value-of select="site_email" disable-output-escaping="yes" /></dd>

	<dt>site_description *</dt>
    	<dd><xsl:value-of select="site_description" disable-output-escaping="yes" /></dd>

	<dt>site_keywords *</dt>
    	<dd><xsl:value-of select="site_keywords" disable-output-escaping="yes" /></dd>

	<dt>site_icon *</dt>
    	<dd><xsl:value-of select="site_icon" disable-output-escaping="yes" /></dd>

	<dt>site_head *</dt>
    	<dd><xsl:value-of select="site_head" disable-output-escaping="yes" /></dd>

	<dt>page_image</dt>
    	<dd><xsl:value-of select="page_image" disable-output-escaping="yes" /></dd>

	<dt>page_author</dt>
    	<dd><xsl:value-of select="page_author" disable-output-escaping="yes" /></dd>

	<dt>page_publisher</dt>
    	<dd><xsl:value-of select="page_publisher" disable-output-escaping="yes" /></dd>

	<dt>prefix</dt>
    	<dd><xsl:value-of select="prefix" disable-output-escaping="yes" /></dd>

	<dt>suffix</dt>
    	<dd><xsl:value-of select="suffix" disable-output-escaping="yes" /></dd>

	<dt>text</dt>
    	<dd><xsl:value-of select="text" disable-output-escaping="yes" /></dd>

	<dt>extra</dt>
    	<dd><xsl:value-of select="extra" disable-output-escaping="yes" /></dd>

	<dt>navigation</dt>
    	<dd><xsl:value-of select="navigation" disable-output-escaping="yes" /></dd>

	<dt>error</dt>
    	<dd><xsl:value-of select="error" disable-output-escaping="yes" /></dd>

	<dt>debug</dt>
    	<dd><xsl:value-of select="debug" disable-output-escaping="yes" /></dd>

	</dl>

	<p>* signals attributes that can be changed through configuration panels</p>

</xsl:template>

</xsl:stylesheet>