<xsl:stylesheet version="1.0"
 xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:strip-space elements="*"/>

    <xsl:template match="/">
        <tests>
            <xsl:apply-templates />
        </tests>
    </xsl:template>

    <xsl:template match="/testsuite">
        <tests>
            <xsl:apply-templates select="testcase" />
        </tests>
    </xsl:template>

    <xsl:template match="testcase[error|failure]">
        <test>
            <xsl:value-of select="@class"/>::<xsl:value-of select="@name"/>
        </test>
    </xsl:template>
</xsl:stylesheet>
