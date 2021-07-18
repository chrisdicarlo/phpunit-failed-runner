<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="xml" indent="yes"/>

    <xsl:template match="/">
        <xsl:apply-templates />
    </xsl:template>

<xsl:template match="tests">
        <xsl:apply-templates select="./test[@status = '3']" />
</xsl:template>

<xsl:template match="test">
    <xsl:choose>
        <xsl:when test="@status = 3">
            <xsl:value-of select="@methodName" />
            <xsl:if test="position() != last()">
                <xsl:text>,</xsl:text>
            </xsl:if>
        </xsl:when>
        <xsl:otherwise />
    </xsl:choose>
</xsl:template>
</xsl:stylesheet>
