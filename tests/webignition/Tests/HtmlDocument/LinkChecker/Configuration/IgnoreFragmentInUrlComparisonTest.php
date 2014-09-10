<?php

namespace webignition\Tests\HtmlDocument\LinkChecker\Configuration;

class IgnoreFragmentInUrlComparisonTest extends ConfigurationTest {
    
    public function testGetDefault() {        
        $this->assertFalse($this->getConfiguration()->ignoreFragmentInUrlComparison());
    }
    
    public function testEnableReturnsSelf() {
        $this->assertEquals($this->getConfiguration(), $this->getConfiguration()->enableIgnoreFragmentInUrlComparison());
    }
    
    
    public function testDisableReturnsSelf() {
        $this->assertEquals($this->getConfiguration(), $this->getConfiguration()->disableIgnoreFragmentInUrlComparison());
    }    
    
    public function testEnableGetsTrue() {             
        $this->assertTrue($this->getConfiguration()->enableIgnoreFragmentInUrlComparison()->ignoreFragmentInUrlComparison());
    }
    
    public function testDisableGetsFalse() {             
        $this->assertFalse($this->getConfiguration()->disableIgnoreFragmentInUrlComparison()->ignoreFragmentInUrlComparison());
    }    
    
}