<!-- count-failed.xsl -->
<xsl:stylesheet version = '1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform'>
<xsl:template match="/">
    <xsl:value-of select="count(tests/test)" />
</xsl:template>
</xsl:stylesheet>
