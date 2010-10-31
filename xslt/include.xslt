<?xml version="1.0" encoding="UTF-8"?>
<x:stylesheet version="1.0" xmlns:x="http://www.w3.org/1999/XSL/Transform" xmlns:c="http://www.w3.org/2006/03/hcard" xmlns:v="http://pornel.net/hcard-validator" xmlns='http://www.w3.org/1999/xhtml' xmlns:h='http://www.w3.org/1999/xhtml'>
  <x:output encoding="UTF-8" method="xml" />

  <!--  | h:object[@data and contains(concat(' ',normalize-space(@class),' '),' include ')]-->

  <x:template match="/">

    <x:for-each select="(//h:div[ancestor::h:span|ancestor::h:p|ancestor::h:abbr])[1]">
       <v:error id="block_in_inline">Found &lt;div> inside &lt;<v:arg><x:value-of select="local-name((ancestor::h:span|ancestor::h:p|ancestor::h:abbr)[1])" /></v:arg></v:error>
    </x:for-each>

    <x:if test="//h:a[contains(@href,'#') and contains(concat(' ',normalize-space(@class),' '),' include ')] | h:object[contains(@data,'#') and contains(concat(' ',normalize-space(@class),' '),' include ')]">
      <v:warn id="include_used" href="http://microformats.org/wiki/include">Include pattern used</v:warn>
    </x:if>

    <!-- it works by copying whole document with includes inlined -->
    <x:apply-templates select="@*|node()">
       <x:with-param name="depth"><x:value-of select="1" /></x:with-param>
     </x:apply-templates>
  </x:template>

  <x:template match="h:a[contains(concat(' ',normalize-space(@class),' '),' include ')]" priority="10">
    <x:param name="depth" />

    <x:if test="$depth = 1 and not(normalize-space(.)) and not(.//img[normalize-space(@alt)])">
       <v:error id="include_empty_a" href="http://microformats.org/wiki/include#Hyperlink">Hyperlink used for include must contain text</v:error>
    </x:if>

    <x:call-template name="include">
      <x:with-param name="url"><x:value-of select="@href" /></x:with-param>
      <x:with-param name="depth"><x:value-of select="$depth" /></x:with-param>
    </x:call-template>
  </x:template>

  <x:template match="h:object[contains(concat(' ',normalize-space(@class),' '),' include ')]" priority="10">
    <x:param name="depth" />
    <x:call-template name="include">
      <x:with-param name="url"><x:value-of select="@data" /></x:with-param>
      <x:with-param name="depth"><x:value-of select="$depth" /></x:with-param>
    </x:call-template>
  </x:template>

  <x:template name="include">
    <x:param name="depth" />
    <x:param name="url" />

    <x:variable name="id"><x:value-of select="substring-after($url,'#')" /></x:variable>
    <x:choose>

      <x:when test="$depth &gt; 4">
        <v:error href="http://microformats.org/wiki/include-pattern#in_general" id="include_deep">Deeply nested includes</v:error>
      </x:when>

      <x:when test="substring-before($url,'#')">
        <v:error href="http://microformats.org/wiki/include-pattern#scope" id="include_xdoc">Include must use same-document fragments only</v:error>
      </x:when>

      <x:when test="not(string($id))">
        <v:error href="http://microformats.org/wiki/include-pattern#scope" id="include_frag">Include doesn't contain fragment identifier</v:error>
      </x:when>

      <x:when test="ancestor-or-self::h:*[@id=$id]">
        <v:error href="http://microformats.org/wiki/include-pattern#in_general" id="include_self">Include causes infinite loop</v:error>
      </x:when>

      <!-- this check prevents infinite loops -->
      <x:when test="not(//h:*[@id=$id])">
        <v:error id="include_not_found">Include not found</v:error>
      </x:when>

      <x:otherwise>
        <x:comment>included #<x:value-of select="$id" /></x:comment>
        <x:apply-templates select="//h:*[@id=$id]">
          <x:with-param name="depth"><x:value-of select="$depth + 1" /></x:with-param>
        </x:apply-templates>
      </x:otherwise>
    </x:choose>
  </x:template>

  <!-- XML forbids multiple IDs, so copy them only outside includes -->
  <x:template match="@id" priority="1">
    <x:param name="depth" />
    <x:if test="$depth = 1"><x:copy /></x:if>
  </x:template>

  <!-- this is default template that copies everything else that wasn't modified -->
  <x:template match="*|@*|node()">
    <x:param name="depth" />
    <x:copy>
      <x:apply-templates select="@*|node()">
        <x:with-param name="depth"><x:value-of select="$depth" /></x:with-param>
      </x:apply-templates>
    </x:copy>
  </x:template>
</x:stylesheet>
