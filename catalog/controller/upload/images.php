<?php

class Controlleruploadimages extends Controller
{
    function index()
    {
        $data = [];
        $this->response->setOutput($this->load->twig('upload/images.twig', $data));
    }
}