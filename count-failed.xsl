<xsl:stylesheet version = '1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform'>
<xsl:template match="/">
    <xsl:value-of select="count(tests/test[@status='3'])" />
</xsl:template>
</xsl:stylesheet>
