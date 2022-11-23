<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="xml" indent="yes"/>

    <xsl:template match="/">
        <xsl:apply-templates />
    </xsl:template>

    <xsl:template match="tests">
        <xsl:apply-templates select="./test" />
    </xsl:template>

    <xsl:template match="test">
        <xsl:call-template name="backslashescape">
            <xsl:with-param name="str" select="." />
        </xsl:call-template>

        <xsl:if test="position() != last()">
            <xsl:text>|</xsl:text>
        </xsl:if>
    </xsl:template>

    <xsl:template name="backslashescape">
        <xsl:param name="str" select="."/>
        <xsl:choose>
            <xsl:when test="contains($str, '\')">
            <xsl:value-of select="concat(substring-before($str, '\'), '\\' )"/>
            <xsl:call-template name="backslashescape">
                <xsl:with-param name="str" select="substring-after($str, '\')"/>
            </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="$str"/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

<!-- <xsl:template match="tests">
    <xsl:apply-templates select="./test[@status = '3']" />
</xsl:template>

<xsl:template match="test">
    <xsl:value-of select="@methodName" />
    <xsl:if test="position() != last()">
        <xsl:text>|</xsl:text>
    </xsl:if>
</xsl:template> -->
</xsl:stylesheet>
