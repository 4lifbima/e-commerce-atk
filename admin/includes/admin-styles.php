<?php
// Admin Global Styles
// Include this in the <head> tag of all admin pages
?>
<style>
    /* Global Admin Layout */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    html, body {
        width: 100%;
        height: 100%;
    }
    
    body {
        display: flex;
        flex-direction: column;
    }
    
    .main-content {
        flex: 1;
        width: 100%;
        overflow-y: auto;
    }
    
    /* Desktop: Sidebar is always visible on left */
    @media (min-width: 769px) {
        body {
            flex-direction: row;
        }
        
        .main-content {
            margin-left: 256px; /* w-64 = 256px */
        }
    }
    
    /* Mobile: Sidebar hidden by default */
    @media (max-width: 768px) {
        .main-content {
            margin-left: 0;
            padding-top: 20px;
        }
    }
    
    /* Smooth transitions */
    .transition-all {
        transition: all 0.3s ease;
    }
</style>
