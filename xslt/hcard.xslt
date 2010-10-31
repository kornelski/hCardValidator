<?xml version="1.0" encoding="UTF-8" ?>
<x:stylesheet version="1.0" xmlns:x="http://www.w3.org/1999/XSL/Transform" xmlns:c="http://www.w3.org/2006/03/hcard" xmlns:v="http://pornel.net/hcard-validator" xmlns='http://www.w3.org/1999/xhtml' xmlns:h='http://www.w3.org/1999/xhtml'>
	<x:output encoding="UTF-8" method="xml" />

	<x:template match="/*">
	  <v:results>	    
	    
      <x:if test="not(//h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])">
        <v:error id="no_vcards" href="http://microformats.org/wiki/hcard-authoring#A_5_minute_primer_to_using_hCard" />
      </x:if>        
      
      <x:apply-templates select="//h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')]"/>
          
      <x:call-template name="check-xhtml" />
      
      <x:if test="count(//h:*[not(@id) and contains(concat(' ',normalize-space(@class),' '),' vcard ')]) &gt; 1">
        <v:info id="multiple_idless_vcards" href="http://microformats.org/wiki/hcard-parsing#URL_handling" />
      </x:if>
      
    	<x:if test="//h:*[contains(concat(' ',normalize-space(@class),' '),' hcard ') and not(contains(concat(' ',normalize-space(@class),' '),' vcard '))]">
    	  <v:warn id="hcard_class" href="http://microformats.org/wiki/hcard-parsing#root_class_name">Element contains hcard class. hCard microformat recognizes only vcard class name.</v:warn>
    	</x:if>
    	
    	<x:if test="//h:*[contains(concat(' ',normalize-space(@class),' '),' vevent ')]">
    	  <v:info id="vevent_class">hCalendar ignored</v:info>
    	</x:if>    	
    	
    </v:results>
	</x:template>
	
	<x:template name="check-xhtml">
	  <x:choose>
	    <x:when test="local-name(.) = 'html' and namespace-uri(.) != 'http://www.w3.org/1999/xhtml'">
	      <v:error id="root_xmlns" href="http://www.w3.org/TR/xhtml1/#strict">&lt;html&gt; not in XHTML namespace (add xmlns='http://www.w3.org/1999/xhtml')</v:error>
	    </x:when>
    
	    <x:when test="namespace-uri(.) != 'http://www.w3.org/1999/xhtml'">
	      <v:warn id="not_xhtml" href="http://www.w3.org/TR/xhtml1/#strict">Document is in "<v:arg><x:value-of select="namespace-uri(.)" /></v:arg>" namespace rather than XHTML.</v:warn>
	    </x:when>
    
      <x:otherwise>
        <x:choose>
    	    <x:when test="not(/h:html/h:head)">
    	      <v:error id="no_head" href="http://validator.w3.org/">Can't find &lt;head&gt;</v:error>
    	    </x:when>
    
    	    <x:when test="not(/h:html/h:head[string(@profile)])">
    	      <!-- <x:if test="//h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')]">
    	        <v:warn id="no_head_profile" href="http://microformats.org/wiki/hcard-profile#Usage">&lt;head&gt; doesn't have profile='http://www.w3.org/2006/03/hcard'</v:warn>
    	      </x:if>   -->
    	    </x:when>

          <x:otherwise>
      	    <x:if test="/h:html/h:head[contains(normalize-space(@profile),' ')]">
      	      <v:warn id="multiple_head_profiles" href="http://microformats.org/wiki/profile-uris#Validator_warning">&lt;head profile=""&gt; uses multiple profile URIs. HTML specification is self-contradictory about this case. Use single <code>http://purl.org/uF/2008/03/</code> profile instead.</v:warn>
      	    </x:if>
    	    
      	    <x:if test="not(/h:html/h:head[contains(@profile,'http://www.w3.org/2006/03/hcard') or contains(@profile,'http://purl.org/uF/2008/03/') or contains(@profile,'http://purl.org/uF/hCard/1.0/')])">
      	      <x:choose>
      	        <x:when test="/h:html/h:head[@profile='http://purl.org/dc/elements/1.1/' or @profile='http://purl.org/dc/terms/' or @profile='http://web.resource.org/cc/' or @profile='http://gmpg.org/xfn/1#' or @profile='http://www.gmpg.org/xfn/11' or @profile='http://gmpg.org/xfn/11']">
      	          <v:warn id="wrong_head_profile" href="http://microformats.org/wiki/hcard-profile#Usage">&lt;head&gt; uses profile <v:arg><x:value-of select="/h:html/h:head/@profile"/></v:arg> unrelated to 'http://www.w3.org/2006/03/hcard'</v:warn>
      	        </x:when>  
                <x:otherwise>
      	          <v:info id="unknown_head_profile" href="http://microformats.org/wiki/hcard-profile#Usage">&lt;head&gt; uses profile <v:arg><x:value-of select="/h:html/h:head/@profile"/></v:arg> unrelated to 'http://www.w3.org/2006/03/hcard'</v:info>
      	        </x:otherwise>
      	      </x:choose>    
      	    </x:if>  
    	    </x:otherwise>  
        </x:choose>
        
        <x:if test="not(/h:html/h:body)">
  	      <v:error id="no_body" href="http://validator.w3.org/">Can't find &lt;body&gt;</v:error>
  	    </x:if>  	  
  	    
      </x:otherwise>
    </x:choose>
  </x:template>
	
	<x:template name="value">
	  <x:param name="nesting" />
	  
	  <x:if test=".//h:*[contains(concat(' ',normalize-space(@class),' '),' value ') and contains(concat(' ',normalize-space(@class),' '),' type ') and 
      $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
      <v:warn id="type_value" href="http://microformats.org/wiki/hcard#type_with_unspecified_value">Element contains both type and value classes</v:warn>
    </x:if>
    
    <x:if test="local-name(.) = 'pre' and .//h:*[not(local-name(.)='abbr')]"><v:warn id="pre_elements">Elements inside &lt;pre&gt;</v:warn></x:if>
    
    <x:if test=".//h:del">
      <v:warn id="del_value" href="http://microformats.org/wiki/hcard-parsing#DEL_element_handling">hCard parsers may not ignore &lt;del&gt; content</v:warn>
    </x:if>
	  
	  <x:choose>	    
	    <x:when test="(local-name(.) = 'img' or local-name(.) = 'area') and @alt">
	      <x:value-of select="@alt" />
	    </x:when>
	    
	    <x:when test="local-name(.) = 'br' or local-name(.) = 'hr' or local-name(.) = 'img' or local-name(.) = 'input'">
	      <v:error id="br_value" href="http://microformats.org/wiki/hcard-parsing#all_properties">Empty element &lt;<v:arg><x:value-of select="local-name(.)" /></v:arg>&gt; used for value</v:error>
	    </x:when>
	    
	    <x:when test="local-name(.) = 'abbr'">
	      <x:choose>
    	    <x:when test="not(@title)">
    	      <v:error id="abbr_no_title" href="http://microformats.org/wiki/abbr-design-pattern">Missing title attribute on &lt;abbr&gt;</v:error>
    	      <x:apply-templates mode="value"/>
          </x:when>
          <x:when test="descendant-or-self::h:*[contains(concat(' ',normalize-space(@class),' '),' value ') and 
            $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
            <v:warn id="title_and_value" href="http://microformats.org/wiki/hcard#Human_vs._Machine_readable">Value has both title and class="value" elements (title takes precedence)</v:warn>
          </x:when>      
        </x:choose>  
        <x:value-of select="@title" />
  	  </x:when>
  	  
      <x:otherwise>
        <x:if test="local-name(.) = 'acronym' and @title">
    	    <v:warn id="acronym" href="http://microformats.org/wiki/abbr-design-pattern">&lt;acronym&lt; doesn't have special handling like &lt;abbr&gt;</v:warn>
    	  </x:if>

        <x:choose>
          <x:when test=".//h:*[contains(concat(' ',normalize-space(@class),' '),' value ') and 
            $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
            
            <x:if test="count(.//h:*[contains(concat(' ',normalize-space(@class),' '),' value ') and 
              $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]) &gt; 1">
              <v:info id="split_value" href="http://microformats.org/wiki/hcard-parsing#Value_excerpting">Value is split in <v:arg><x:value-of select="count(.//h:*[contains(concat(' ',normalize-space(@class),' '),' value ')])"/></v:arg> parts</v:info>
            </x:if>
<!-- $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')]) -->

      	    <x:apply-templates mode="value" select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' value ') and 
      	      $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]" />
      	  </x:when>
      	  <x:otherwise>
      	    <x:apply-templates mode="value"/>
      	  </x:otherwise>  
        </x:choose>          
      </x:otherwise>	  
    </x:choose>  
	</x:template>	
	
	<x:template match="h:*[contains(concat(' ',normalize-space(@class),' '),' type ') and not(contains(concat(' ',normalize-space(@class),' '),' value '))]" mode="value">
	  <x:comment>type skipped</x:comment>
	</x:template>  
	
	<x:template match="h:abbr[@title]" mode="value" priority="-1">
	  <v:warn id="abbr_in_value">&lt;abbr&gt; in value</v:warn>
	  <x:apply-templates mode="value" />
	</x:template>  

	
	<x:template match="h:br" mode="value">
	  <x:text>&#x0a;</x:text>
	</x:template>

	<x:template match="text()" mode="value">
	  <x:choose>
	    <x:when test="not(.) or ancestor::h:pre"><x:copy /></x:when>
	    <x:when test="not(normalize-space(.))"><x:text> </x:text></x:when>
  	  <x:otherwise>
	      <x:value-of select="substring(normalize-space(concat('X',.)),2)" />
	      <x:if test="not(normalize-space(substring(.,string-length(.))))"><x:text> </x:text></x:if>
	    </x:otherwise>
	  </x:choose>
	</x:template>

	<x:template match="h:img[string(@alt) and not(contains(concat(' ',normalize-space(@class),' '),' value '))]" priority="-1" mode="value">
	  <v:warn id="img_in_value" href="http://microformats.org/wiki/hcard-faq#Why_is_IMG_alt_not_being_picked_up">Image with alt inside value</v:warn>
	</x:template>
	
	<x:template match="h:del" mode="value" />
	
	<x:template match="h:pre" mode="value">
	  <x:if test=".//h:*[not(local-name(.)='abbr')]"><v:warn id="pre_elements">Elements inside &lt;pre&gt;</v:warn></x:if>
	  <x:apply-templates mode="value" />
  </x:template>
	
	<!-- check vcard. avoid vcards of agents - these are handled separately -->
	<x:template name="vcard" match="h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ') and not(contains(concat(' ',normalize-space(@class),' '),' agent '))]">
	  <c:vcard>
	    <x:variable name="nesting"><x:value-of select="1 + count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])" /></x:variable>
	    	    	    
	    <x:if test="@id">
	      <c:source>#<x:value-of select="@id" /></c:source>
	    </x:if>  	    	          
	    	    
	    <x:if test="local-name(.) = 'address'">
	      <v:warn id="vcard_address" href="http://microformats.org/wiki/hcard-faq#Should_I_use_ADDRESS_for_hCards">hCard microformat in &lt;address&gt;</v:warn>
	    </x:if>  	    	    
	    	    	    
	<!--    <x:if test="/h:html/h:head/h:title">
	      <c:name>
	        <x:value-of select="/h:html/h:head/h:title" /><x:if test="@id"><x:text> (</x:text><x:value-of select="@id" />)</x:if>
	      </c:name>
	    </x:if>  -->
	    
      <x:if test=".//h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')]">
        <v:warn id="nested_vcard" href="http://microformats.org/wiki/hcard-parsing#nested_hCards">Nested vcard (it's allowed by the spec, but not supported properly by the validator)</v:warn>
      </x:if>
            
      <!-- read adr -->
      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' adr ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">                
          <c:adr>
            <x:if test="local-name(.) = 'address'">
              <v:error id="adr_address" href="http://microformats.org/wiki/hcard-faq#Should_I_use_ADDRESS_for_hCards">adr microformat in &lt;address&gt;</v:error>
            </x:if>
            
            <x:if test="local-name(.) = 'abbr' and not(contains(concat(' ',normalize-space(@class),' '),' geo ')) and not(contains(concat(' ',normalize-space(@class),' '),' org '))">
              <v:error id="adr_abbr" href="http://microformats.org/wiki/hcard-faq#Should_I_use_ADDRESS_for_hCards">adr microformat in &lt;abbr&gt;</v:error>
            </x:if>
          
            <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' type ') and 
$nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
                <c:type><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:type>
 
            </x:for-each>
          
            <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' post-office-box ') and 
$nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
                <c:post-office-box><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:post-office-box>

            </x:for-each>

            <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' street-address ') and 
$nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
                <c:street-address><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:street-address>

            </x:for-each>
          
            <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' extended-address ') and 
$nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
                <x:if test="ancestor-or-self::h:*[not(local-name(.) = 'abbr') and contains(concat(' ',normalize-space(@class),' '),' fn ')]">
                  <v:info id="place_address" href="http://microformats.org/wiki/hcard-brainstorming#Named_locations">This hCard describes place, not person</v:info>
                </x:if>
                <c:extended-address><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:extended-address>

            </x:for-each>
          
            <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' region ') and 
$nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
                <c:region><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:region>

            </x:for-each>
          
            <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' locality ') and 
$nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
                <c:locality><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:locality>

            </x:for-each>
          
            <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' postal-code ') and 
$nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
                <c:postal-code><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:postal-code>
 
            </x:for-each>
          
            <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' country-name ') and 
$nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
                <c:country-name><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:country-name>

            </x:for-each>
          </c:adr>
      </x:for-each>
      
      <!-- orphaned adr props -->
      
      <x:for-each select=".//h:*[not(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' adr ')]) and 
        (contains(concat(' ',normalize-space(@class),' '),' post-office-box ') or
         contains(concat(' ',normalize-space(@class),' '),' street-address ') or
         contains(concat(' ',normalize-space(@class),' '),' extended-address ') or
         contains(concat(' ',normalize-space(@class),' '),' region ') or
         contains(concat(' ',normalize-space(@class),' '),' locality ') or
         contains(concat(' ',normalize-space(@class),' '),' postal-code ') or
         contains(concat(' ',normalize-space(@class),' '),' country-name ') ) and
         $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
        <v:warn id="orphan_adr_subprop" href="http://microformats.org/wiki/hcard-faq#Can_you_mix_a_property_and_its_sub-properties">Subproperty of adr found outside adr <v:arg><x:value-of select="@class"/></v:arg></v:warn>
      </x:for-each>
      
      
      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' agent ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          <c:agent>
            <x:choose>
              <x:when test="contains(concat(' ',normalize-space(@class),' '),' vcard ')">
                <x:call-template name="vcard" select="." />              
              </x:when>
              <x:when test="descendant-or-self::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')]">
                <v:warn href="http://www.w3.org/2002/12/cal/rfc2426#sec3.5.4" id="agent_nested_vcard">agent property does not have vcard class on same element</v:warn>                
                <x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template>
              </x:when>
              <x:otherwise>
                <v:warn href="http://www.w3.org/2002/12/cal/rfc2426#sec3.5.4" id="agent_not_vcard">agent property does not contain hcard</v:warn>
                <x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template>
              </x:otherwise>
            </x:choose>
          </c:agent>
      </x:for-each>

      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' bday ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          <c:bday><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:bday>
      </x:for-each>

	    <x:if test=".//h:*[(contains(concat(' ',normalize-space(@class),' '),' b-day ') or contains(concat(' ',normalize-space(@class),' '),' birthday ') or contains(concat(' ',normalize-space(@class),' '),' bdate ') or contains(concat(' ',normalize-space(@class),' '),' birth-date ') or contains(concat(' ',normalize-space(@class),' '),' birthdate ')) and not(contains(concat(' ',normalize-space(@class),' '),' bday ')) and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
    	  <v:warn id="birthday_class" href="http://microformats.org/wiki/hcard#Property_Exceptions">hCard contains <v:arg><x:value-of select="@class"/></v:arg> class. Use bday.</v:warn>
    	</x:if>



      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' class ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          <c:class><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:class>
      </x:for-each>

      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' category ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          <c:category>
            <x:choose>
              <x:when test="(local-name(.) = 'a' or local-name(.) = 'area') and contains(concat(' ',normalize-space(@rel),' '),' tag ')">
                <x:value-of select="@href" />
              </x:when>
              <x:when test=".//h:a[contains(concat(' ',normalize-space(@rel),' '),' tag ')]">
                <v:error id="nested_rel">&lt;a rel=tag&gt; inside category (use class on &lt;a&gt; instead)</v:error>
                <x:value-of select=".//h:a[contains(concat(' ',normalize-space(@rel),' '),' tag ')]/@href" />
              </x:when>
              <x:otherwise>
                <x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template>
              </x:otherwise>
            </x:choose>              
          </c:category>
      </x:for-each>

      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' email ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          <c:email>
            <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' type ')]">
              <c:type><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:type>
            </x:for-each>
          
            <c:value>
              <x:choose>
                <x:when test="(local-name(.) = 'a' or local-name(.) = 'area') and @href">
                  <x:value-of select="@href" /><!-- will check href later -->
                </x:when>
                <x:otherwise>
                  <x:choose>                         
                    <x:when test="count(.//h:a[contains(@href,'mailto:')]) &gt; 1">
                       <v:error id="multiple_emails">Multiple <v:arg><x:value-of select="count(.//h:a[contains(@href,'mailto:')])" /></v:arg> e-mail links in a single e-mail class</v:error>
                    </x:when>                                    
                    <x:when test=".//h:a[contains(@href,'mailto:') and not(contains(concat(' ',normalize-space(@class),' '),' email ')) and not(contains(concat(' ',normalize-space(@class),' '),' value '))]">
                      <v:warn id="a_in_email">Email link nested inside e-mail property (put class on &lt;a&gt;)</v:warn>                
                    </x:when>
                  </x:choose><!--                  
                  mailto: is here to avoid making distinction between <span>joe@example.com</span> and <a href="mailto:joe@example.com"> 
                  and to give errors if links are messed-up like <a href="joe@example.com"> 
                  -->mailto:<x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></x:otherwise>
              </x:choose>
            </c:value>
          </c:email>
      </x:for-each>     

      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' geo ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          <c:geo>                
            <x:choose>
              <x:when test="not(.//h:*[contains(concat(' ',normalize-space(@class),' '),' latitude ')]) and not(.//h:*[contains(concat(' ',normalize-space(@class),' '),' longitude ')])">
                <c:value><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:value>
              </x:when>
              <x:otherwise>
                <x:if test="not(.//h:*[contains(concat(' ',normalize-space(@class),' '),' latitude ')])">
                  <v:error id="latitude_missing" href="http://microformats.org/wiki/hcard-cheatsheet#Geo">Latitude is missing</v:error>
                </x:if>
                <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' latitude ')]">
                  <c:latitude><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:latitude>
                </x:for-each>

                <x:if test="not(.//h:*[contains(concat(' ',normalize-space(@class),' '),' longitude ')])">
                  <v:error id="longitude_missing" href="http://microformats.org/wiki/hcard-cheatsheet#Geo">Longitude is missing</v:error>
                </x:if>
                <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' longitude ')]">
                  <c:longitude><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:longitude>
                </x:for-each>              
              </x:otherwise>  
            </x:choose>    
          </c:geo>
      </x:for-each>

      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' key ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          <c:key><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:key>
      </x:for-each>

      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' label ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          <c:label>
            <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' type ')]">
              <c:type><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:type>
            </x:for-each>
            <c:value>
              <x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template>
            </c:value>
          </c:label>
      </x:for-each>

      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' logo ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          <c:logo>
            <x:choose>
              <x:when test="local-name(.)='a' or local-name(.) = 'area'">
                <x:value-of select="@href" />
              </x:when>            
              <x:when test="local-name(.)='img'">
                <x:value-of select="@src" />
              </x:when>
              <x:when test="local-name(.)='object'">
                <x:value-of select="@data" />
              </x:when>
              <x:otherwise>
                <x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template>
              </x:otherwise>  
            </x:choose>
		</c:logo>
      </x:for-each>

      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' mailer ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          <c:mailer><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:mailer>
      </x:for-each>

      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' fn ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          <c:fn><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:fn>
      </x:for-each>

      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' n ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
        
          <x:if test=".//h:*[contains(concat(' ',normalize-space(@class),' '),' prefix ') or contains(concat(' ',normalize-space(@class),' '),' suffix ') or contains(concat(' ',normalize-space(@class),' '),' honorific ')]">
        	  <v:warn id="honorific_class" href="http://microformats.org/wiki/hcard-cheatsheet#Properties__.28Class_Names.29">Uses <v:arg><x:value-of select="@class"/></v:arg> class instead of honorific-prefix/honorific-suffix</v:warn>
        	</x:if>
        	
        	<x:if test=".//h:*[(contains(concat(' ',normalize-space(@class),' '),' last-name ') or contains(concat(' ',normalize-space(@class),' '),' surname ') or contains(concat(' ',normalize-space(@class),' '),' lastname ')) and not(contains(concat(' ',normalize-space(@class),' '),' family-name '))]">
        	  <v:warn id="lastname_class" href="http://microformats.org/wiki/hcard-cheatsheet#Properties__.28Class_Names.29">Uses <v:arg><x:value-of select="@class"/></v:arg> class instead of family-name</v:warn>
        	</x:if>
        	
        	<x:if test=".//h:*[contains(concat(' ',normalize-space(@class),' '),' first-name ') or contains(concat(' ',normalize-space(@class),' '),' firstname ')]">
        	  <v:warn id="lastname_class" href="http://microformats.org/wiki/hcard-cheatsheet#Properties__.28Class_Names.29">Uses <v:arg><x:value-of select="@class"/></v:arg> class instead of given-name</v:warn>
        	</x:if>
        	
        	<x:if test=".//h:*[contains(concat(' ',normalize-space(@class),' '),' additional-names ') or contains(concat(' ',normalize-space(@class),' '),' middle-name ')]">
        	  <v:warn id="additional_names_class" href="http://microformats.org/wiki/hcard-cheatsheet#Properties__.28Class_Names.29">Uses <v:arg><x:value-of select="@class"/></v:arg> class instead of additional-name</v:warn>
        	</x:if>
        
          <c:n>
            <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' honorific-prefix ') and 
$nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
                <c:honorific-prefix><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:honorific-prefix>
            </x:for-each>

            <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' given-name ') and 
$nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
                <c:given-name><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:given-name>

            </x:for-each>

            <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' additional-name ') and 
$nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
                <c:additional-name><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:additional-name>

            </x:for-each>

            <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' family-name ') and 
$nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
                <c:family-name><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:family-name>

            </x:for-each>

            <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' honorific-suffix ') and 
$nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
                <c:honorific-suffix><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:honorific-suffix>

            </x:for-each>
          </c:n>
      </x:for-each>

      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' nickname ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          <c:nickname><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:nickname>
      </x:for-each>

      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' note ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          <c:note><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:note>
      </x:for-each>

      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' org ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          
          <!-- org is in fn (which is not abbr@title) and this may imply company name, but validator later needs to compare actual values to be sure -->
          <x:if test="ancestor-or-self::h:*[contains(concat(' ',normalize-space(@class),' '),' fn ') and not(local-name(.) = 'abbr' and @title) and
            $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
            <v:flag id="org_in_fn" />
          </x:if>
                    
          <x:if test=".//h:*[contains(concat(' ',normalize-space(@class),' '),' organisation-name ') or contains(concat(' ',normalize-space(@class),' '),' organisation-unit ') and 
            $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
        	  <v:error id="organisation_spelling" href="http://microformats.org/wiki/hcard-cheatsheet#Properties__.28Class_Names.29">hCard spells organization classes with z</v:error>
        	</x:if>    
        
    	    <x:if test=".//h:*[contains(concat(' ',normalize-space(@class),' '),' unit ') and not(contains(concat(' ',normalize-space(@class),' '),' organization-unit '))]">
        	  <v:warn id="unit_class" href="http://microformats.org/wiki/hcard-cheatsheet#Properties__.28Class_Names.29">org contains unit class (organization-unit is expected)</v:warn>
        	</x:if>
        
          <c:org>
            <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' organization-name ')]">
              <c:organization-name><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:organization-name>
            </x:for-each>
          
            <!-- The "ORG" property has two subproperties, organization-name and organization-unit. Very often authors only publish the organization-name. 
            Thus if an "ORG" property has no "organization-name" inside it, then its entire contents MUST be treated as the "organization-name". -->
            <x:if test="not(.//h:*[contains(concat(' ',normalize-space(@class),' '),' organization-name ') and 
              $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])])">
              <c:organization-name><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:organization-name>
            </x:if>

            <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' organization-unit ') and 
              $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
            
              <x:if test="not(contains(concat(' ',normalize-space(@class),' '),' extended-address ')) and ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' adr ')]">
                <v:warn id="org_unit_extended_adr" href="http://microformats.org/wiki/hcard-examples#Organizations_and_Departments">Organization unit in address might have extended-address class</v:warn>
              </x:if>            
            
              <c:organization-unit><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:organization-unit>
            </x:for-each>
          
          </c:org>
        
      </x:for-each>

      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' photo ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          
          <c:photo>
            <x:choose>
              <x:when test="local-name(.)='a' or local-name(.) = 'area'">
                <x:value-of select="@href" />
              </x:when>            
              <x:when test="local-name(.)='img'">
                <x:value-of select="@src" />
              </x:when>
              <x:when test="local-name(.)='object'">
                <x:value-of select="@data" />
              </x:when>
            
              <x:when test=".//h:img">
                <v:error href="http://microformats.org/wiki/hcard-parsing#properties_of_type_URL_or_URI_or_UID" id="nested_photo">&lt;img&gt; inside photo element (add class to &lt;img&gt; instead)</v:error>
                <x:value-of select=".//h:img/@src" />
              </x:when>
              <x:when test=".//h:a">
                <v:error href="http://microformats.org/wiki/hcard-parsing#properties_of_type_URL_or_URI_or_UID" id="nested_photo">&lt;a&gt; inside photo element (add class to &lt;a&gt; instead)</v:error>
                <x:value-of select=".//h:a/@href" />
              </x:when>
              <x:when test=".//h:object">
                <v:error href="http://microformats.org/wiki/hcard-parsing#properties_of_type_URL_or_URI_or_UID" id="nested_photo">&lt;object&gt; inside photo element (add class to &lt;object&gt; instead)</v:error>
                <x:value-of select=".//h:object/@data" />
              </x:when>
            
              <x:otherwise>
                <v:error href="http://microformats.org/wiki/hcard-parsing#properties_of_type_URL_or_URI_or_UID" id="photo_as_value">Photo uses <v:arg><x:value-of select="local-name(.)"/></v:arg> instead of &lt;img&gt;, &lt;a&gt; or &lt;object&gt;</v:error>
                <x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template>
              </x:otherwise>  
            </x:choose>
          </c:photo>
      </x:for-each>

      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' rev ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          <c:rev><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:rev>

      </x:for-each>

      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' role ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          <c:role><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:role>

      </x:for-each>

      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' sort-string ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          <c:sort-string><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:sort-string>

      </x:for-each>

      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' sound ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          <c:sound><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:sound>

      </x:for-each>

      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' title ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          <c:title><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:title>

      </x:for-each>

      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' tel ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          <c:tel>
            <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' type ') and 
              $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
              <c:type><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:type>
            </x:for-each>

            <c:value><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:value>
          </c:tel>

      </x:for-each>

      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' tz ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          <c:tz><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:tz>

      </x:for-each>


      <x:if test=".//h:a[contains(concat(' ',normalize-space(@class),' '),' uniqid ')]">
    	  <v:warn href="http://microformats.org/wiki/hcard-cheatsheet#Properties__.28Class_Names.29" id="uniqid_class">hCard contains uniqid class (only uid is recognized)</v:warn>
    	</x:if>
    	
      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' uid ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          <c:uid>
            <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' type ') and 
              $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
              <c:type><x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template></c:type>
            </x:for-each>
            
            <c:value>
              <x:choose>            
                <x:when test="local-name(.)='a' or local-name(.) = 'area'">
                  <x:value-of select="@href" />
                </x:when>                        
                <x:when test=".//h:a">
                  <v:error href="http://microformats.org/wiki/hcard-parsing#properties_of_type_URL_or_URI_or_UID" id="nested_uid">&lt;a&gt; inside uid element (add class to &lt;a&gt; instead)</v:error>
                  <x:value-of select=".//h:a/@href" />
                </x:when>
                <x:otherwise>
                  <x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template>
                </x:otherwise>
              </x:choose>
            </c:value>
          </c:uid>

      </x:for-each>

	    <x:if test=".//h:a[contains(@href,'mailto:') and not(ancestor-or-self::h:*[contains(concat(' ',normalize-space(@class),' '),' email ')]) and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
    	  <v:warn id="mailto_no_class">hCard contains mailto: links without email class</v:warn>
    	</x:if>
      
	    <x:if test=".//h:a[contains(concat(' ',normalize-space(@class),' '),' uri ')]">
    	  <v:warn href="http://microformats.org/wiki/hcard-cheatsheet#Properties__.28Class_Names.29" id="uri_class">hCard contains uri class (only url is recognized)</v:warn>
    	</x:if>
    	
      <x:for-each select=".//h:*[contains(concat(' ',normalize-space(@class),' '),' url ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
          <c:url>
            <x:choose>
              <x:when test="local-name(.) = 'img' or local-name(.) = 'object'">
                <x:if test="not(contains(concat(' ',normalize-space(@class),' '),' photo ')) and not(contains(concat(' ',normalize-space(@class),' '),' logo '))">
                  <v:warn id="url_as_img">URL property on an &lt;<v:arg><x:value-of select="local-name(.)"/></v:arg>> element. photo or logo property might be more appropriate.</v:warn>
                </x:if>          
                <x:value-of select="@src" />     
                <x:value-of select="@data" />     
              </x:when>
              
              <x:when test="local-name(.) = 'a' or local-name(.) = 'area'">
                <x:if test="contains(concat(' ',normalize-space(@class),' '),' uid ')">
                  <v:info id="openid" href="http://microformats.org/wiki/hcard-examples#Canonical_Profiles_on_Sites">This URL has uid class and may be used as OpenID</v:info>
                </x:if>
              
                <x:if test="not(@href)"><v:error id="no_href">No href on &lt;a&gt;</v:error></x:if>
                <x:if test="contains(@href,'mailto:')"><v:error href="http://microformats.org/wiki/hcard#More_Semantic_Equivalents" id="email_as_url">Use e-mail class for e-mail links</v:error></x:if>
              
                <x:value-of select="@href" />
              </x:when>
                        
              <x:when test="count(.//h:a[@href]) &gt; 1">
                 <v:error id="multiple_urls" href="http://microformats.org/wiki/hcard-parsing#properties_of_type_URL_or_URI_or_UID">Multiple links in a single url class</v:error>
              </x:when>

              <x:when test=".//h:a[not(contains(concat(' ',normalize-space(@class),' '),' url ')) and not(contains(concat(' ',normalize-space(@class),' '),' value '))]">
                <v:error id="a_in_url" href="http://microformats.org/wiki/hcard-parsing#properties_of_type_URL_or_URI_or_UID">&lt;a&gt; nested inside url property (put class on &lt;a&gt;)</v:error>
                <x:value-of select=".//h:a/@href" />
              </x:when>
            
              <x:otherwise>
                <v:warn href="http://microformats.org/wiki/hcard-parsing#properties_of_type_URL_or_URI_or_UID" id="url_as_value">URL is not in a link</v:warn>
                <x:call-template name="value"><x:with-param name="nesting"><x:value-of select="$nesting"/></x:with-param></x:call-template>
              </x:otherwise>  
            </x:choose>
          </c:url>

      </x:for-each>
      

	    <x:if test=".//h:*[(contains(concat(' ',normalize-space(@class),' '),' organization ') or contains(concat(' ',normalize-space(@class),' '),' organisation ')) and not(contains(concat(' ',normalize-space(@class),' '),' org ')) and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
    	  <v:warn id="organization_class" href="http://microformats.org/wiki/hcard-cheatsheet#Properties__.28Class_Names.29">hCard contains organization class (hCard only recognizes org class)</v:warn>
    	</x:if>

      <!-- usually include class is pre-processed and stripped -->
	    <x:if test=".//h:a[@href and contains(concat(' ',normalize-space(@class),' '),' include ')] | .//h:object[@data and contains(concat(' ',normalize-space(@class),' '),' include ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
    	  <v:warn id="include_used" href="http://microformats.org/wiki/include-pattern">Include pattern not supported</v:warn>
    	</x:if>

	    <x:for-each select=".//h:*[(contains(concat(' ',normalize-space(@class),' '),' nick-name ') or contains(concat(' ',normalize-space(@class),' '),' nick ')) and not(contains(concat(' ',normalize-space(@class),' '),' nickname ')) and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])][1]">
    	  <v:warn id="nick_class" href="http://microformats.org/wiki/hcard#Property_Exceptions">hCard contains '<v:arg><x:value-of select="@class"/></v:arg>' class. Use nickname.</v:warn>
    	</x:for-each>

	    <x:if test=".//h:*[contains(concat(' ',normalize-space(@class),' '),' name ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
    	  <v:warn id="name_class" href="http://microformats.org/wiki/hcard#Property_Exceptions">hCard contains name class (vCard's name is taken from page title and people names are described with fn or n)</v:warn>
    	</x:if>
	    
	    <x:if test=".//h:*[contains(concat(' ',normalize-space(@class),' '),' profile ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
    	  <v:warn id="profile_class" href="http://microformats.org/wiki/hcard#Property_Exceptions">hCard contains profile class (it's ignored in hCard)</v:warn>
    	</x:if>

	    <x:if test=".//h:*[contains(concat(' ',normalize-space(@class),' '),' source ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
    	  <v:warn id="source_class" href="http://microformats.org/wiki/hcard#Property_Exceptions">hCard contains source class (vCard's source is taken from page's URL)</v:warn>
    	</x:if>
    	
	    <x:if test=".//h:*[contains(concat(' ',normalize-space(@class),' '),' prodid ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
    	  <v:warn id="prodid_class" href="http://microformats.org/wiki/hcard#Property_Exceptions">hCard contains prodid class (it's ignored in hCard)</v:warn>
    	</x:if>
    	
    	<x:if test=".//h:*[contains(concat(' ',normalize-space(@class),' '),' version ') and 
        $nesting = count(ancestor::h:*[contains(concat(' ',normalize-space(@class),' '),' vcard ')])]">
    	  <v:warn id="version_class" href="http://microformats.org/wiki/hcard#Property_Exceptions">hCard contains version class (it's ignored in hCard, use rev for versioning)</v:warn>
    	</x:if>
    	    	
    </c:vcard>    
	</x:template>




</x:stylesheet>
