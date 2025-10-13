package com.github.venny_ven.cellar;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.RequestMapping;

// Controllers route traffic around the website, serve pages, and tell the logic what the user did
@Controller
public class HomeController {

    // Example of how to load a variable from application.properties
    @Value("${spring.application.name}")
    private String configVariable;

    // Map the act of going to root directory (`/`) of site to the following method
    @RequestMapping("/")
    public String home() {
        return "home.html";
    }
}
